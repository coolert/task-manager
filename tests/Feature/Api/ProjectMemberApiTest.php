<?php

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;

use Illuminate\Support\Collection;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

it('index: only owner/admin', function () {
    $ctx = setupProjectWithRoles();

    assertRoleMatrix($ctx, 'GET', "/api/projects/{$ctx['project']->id}/members", [
        'owner'    => 200,
        'admin'    => 200,
        'member'   => 403,
        'viewer'   => 403,
        'outsider' => 403,
    ]);

    $response = requestAs(userFor($ctx, 'owner'), 'GET', "/api/projects/{$ctx['project']->id}/members")
        ->assertOk();

    /** @var array<int, array<string, mixed>> $data */
    $data = $response->json('data');

    /** @var Collection<int, array<string, mixed>> $collection */
    $collection = collect($data);
    $ids        = $collection->pluck('user.id')->all();

    expect($ids)->toContain(userFor($ctx, 'admin')->id)
        ->and($ids)->not->toContain(userFor($ctx, 'outsider')->id);
});

it('add member: only owner/admin; duplicate 422; add owner 422, invalid role 422', function () {
    $ctx    = setupProjectWithRoles();
    $target = User::factory()->create();

    // add owner
    requestAs(userFor($ctx, 'owner'), 'POST', "/api/projects/{$ctx['project']->id}/members", [
        'user_id' => $target->id,
        'role'    => ProjectRole::Owner->value,
    ])->assertUnprocessable();

    // invalid role
    requestAs(userFor($ctx, 'owner'), 'POST', "/api/projects/{$ctx['project']->id}/members", [
        'user_id' => $target->id,
        'role'    => 'not-a-role',
    ])->assertUnprocessable();

    // owner ok
    requestAs(userFor($ctx, 'owner'), 'POST', "/api/projects/{$ctx['project']->id}/members", [
        'user_id' => $target->id,
        'role'    => ProjectRole::Member->value,
    ])->assertCreated();

    assertDatabaseHas('project_members', [
        'project_id' => $ctx['project']->id,
        'user_id'    => $target->id,
        'role'       => ProjectRole::Member->value,
    ]);

    // admin ok
    $user = User::factory()->create();

    requestAs(userFor($ctx, 'admin'), 'POST', "/api/projects/{$ctx['project']->id}/members", [
        'user_id' => $user->id,
        'role'    => ProjectRole::Member->value,
    ])->assertCreated();

    // duplicate
    requestAs(userFor($ctx, 'owner'), 'POST', "/api/projects/{$ctx['project']->id}/members", [
        'user_id' => $target->id,
        'role'    => ProjectRole::Member->value,
    ])->assertUnprocessable();

    // outsider forbidden
    requestAs(userFor($ctx, 'outsider'), 'POST', "/api/projects/{$ctx['project']->id}/members", [
        'user_id' => User::factory()->create()->id,
        'role'    => ProjectRole::Member->value,
    ])->assertForbidden();
});

it('update member role: only owner/admin; change role as owner 422; other project member 404; invalid role 422', function () {
    $ctx            = setupProjectWithRoles();
    $user           = User::factory()->create();
    $pm             = ProjectMember::factory()->for($ctx['project'])->for($user)->create(['role' => ProjectRole::Member]);
    $anotherProject = Project::factory()->for(userFor($ctx, 'owner'), 'owner')->create();
    $otherUser      = User::factory()->create();
    $otherPm        = ProjectMember::factory()->for($anotherProject)->for($otherUser)->create(['role' => ProjectRole::Member]);

    requestAs(userFor($ctx, 'owner'), 'PATCH', "/api/projects/{$ctx['project']->id}/members/{$pm->id}", [
        'role' => ProjectRole::Owner->value,
    ])->assertUnprocessable();

    requestAs(userFor($ctx, 'owner'), 'PATCH', "/api/projects/{$ctx['project']->id}/members/{$pm->id}", [
        'role' => 'not-a-role',
    ])->assertUnprocessable();

    assertRoleMatrix($ctx, 'PATCH', "/api/projects/{$ctx['project']->id}/members/{$pm->id}", [
        'owner'    => 200,
        'admin'    => 200,
        'member'   => 403,
        'viewer'   => 403,
        'outsider' => 403,
    ], ['role' => ProjectRole::Admin->value]);

    assertDatabaseHas('project_members', [
        'id'   => $pm->id,
        'role' => ProjectRole::Admin->value,
    ]);

    requestAs(userFor($ctx, 'owner'), 'PATCH', "/api/projects/{$ctx['project']->id}/members/{$otherPm->id}", [
        'role' => ProjectRole::Admin->value,
    ])->assertNotFound();
});

it('delete member: only owner/admin; cannot delete owner 422; not found member 404', function () {
    $ctx = setupProjectWithRoles();

    // delete member
    $user = User::factory()->create();
    $pm   = ProjectMember::factory()->for($ctx['project'])->for($user)->create(['role' => ProjectRole::Member]);
    requestAs(userFor($ctx, 'owner'), 'DELETE', "/api/projects/{$ctx['project']->id}/members/{$pm->id}")
        ->assertNoContent();

    assertDatabaseMissing('project_members', [
        'project_id' => $ctx['project']->id,
        'user_id'    => $user->id,
    ]);

    // delete owner -> 422
    $ownerPm = ProjectMember::query()->where([
        'project_id' => $ctx['project']->id,
        'user_id'    => userFor($ctx, 'owner')->id,
    ])->first() ?? ProjectMember::factory()->for($ctx['project'])->for(userFor($ctx, 'owner'))->create(['role' => ProjectRole::Owner]);

    requestAs(userFor($ctx, 'owner'), 'DELETE', "/api/projects/{$ctx['project']->id}/members/{$ownerPm->id}")
        ->assertUnprocessable();

    requestAs(userFor($ctx, 'admin'), 'DELETE', "/api/projects/{$ctx['project']->id}/members/{$ownerPm->id}")
        ->assertUnprocessable();

    assertDatabaseHas('project_members', [
        'project_id' => $ctx['project']->id,
        'user_id'    => userFor($ctx, 'owner')->id,
        'role'       => ProjectRole::Owner->value,
    ]);

    requestAs(userFor($ctx, 'owner'), 'DELETE', "/api/projects/{$ctx['project']->id}/members/999999")
        ->assertNotFound();

    $anotherProject = Project::factory()->for(userFor($ctx, 'owner'), 'owner')->create();
    $otherUser      = User::factory()->create();
    $otherPm        = ProjectMember::factory()->for($anotherProject)->for($otherUser)->create(['role' => ProjectRole::Member]);

    requestAs(userFor($ctx, 'owner'), 'DELETE', "/api/projects/{$ctx['project']->id}/members/{$otherPm->id}")
        ->assertNotFound();
});
