<?php

use App\Enums\ProjectRole;
use App\Models\ProjectMember;
use App\Models\User;

it('add member: only owner/admin; duplicate 422', function () {
    $ctx    = setupProjectWithRoles();
    $target = User::factory()->create();

    // owner ok
    requestAs(userFor($ctx, 'owner'), 'POST', "/api/projects/{$ctx['project']->id}/members", [
        'user_id' => $target->id,
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

it('update member role: only owner/admin', function () {
    $ctx  = setupProjectWithRoles();
    $user = User::factory()->create();
    $pm   = ProjectMember::factory()->for($ctx['project'])->for($user)->create(['role' => ProjectRole::Member]);

    assertRoleMatrix($ctx, 'PATCH', "/api/projects/{$ctx['project']->id}/members/{$pm->id}", [
        'owner'    => 200,
        'admin'    => 200,
        'member'   => 403,
        'viewer'   => 403,
        'outsider' => 403,
    ], ['role' => ProjectRole::Admin->value]);
});

it('delete member: only owner/admin; cannot delete owner (422)', function () {
    $ctx = setupProjectWithRoles();

    // delete member
    $user = User::factory()->create();
    $pm   = ProjectMember::factory()->for($ctx['project'])->for($user)->create(['role' => ProjectRole::Member]);
    requestAs(userFor($ctx, 'owner'), 'DELETE', "/api/projects/{$ctx['project']->id}/members/{$pm->id}")
        ->assertNoContent();

    // delete owner -> 422
    $ownerPm = ProjectMember::query()->where([
        'project_id' => $ctx['project']->id,
        'user_id'    => userFor($ctx, 'owner')->id,
    ])->first() ?? ProjectMember::factory()->for($ctx['project'])->for(userFor($ctx, 'owner'))->create(['role' => ProjectRole::Owner]);

    requestAs(userFor($ctx, 'owner'), 'DELETE', "/api/projects/{$ctx['project']->id}/members/{$ownerPm->id}")
        ->assertUnprocessable();
});
