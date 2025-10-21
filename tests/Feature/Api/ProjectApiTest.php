<?php

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;

it('lists only projects I own or joined', function () {
    $me       = User::factory()->create();
    $outsider = User::factory()->create();
    $owned    = Project::factory()->for($me, 'owner')->create();
    $other    = Project::factory()->create();
    $joined   = Project::factory()->create();
    ProjectMember::factory()->for($joined)->for($me)->create(['role' => ProjectRole::Member]);

    requestAs($me, 'GET', '/api/projects')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'name', 'description', 'owner_id', 'tasks_count', 'created_at', 'updated_at']],
            'meta' => ['current_page', 'total'],
        ])->assertJsonPath('meta.current_page', 1)
        ->assertJsonFragment(['id' => $owned->id])
        ->assertJsonFragment(['id' => $joined->id])
        ->assertJsonMissing(['id' => $other->id]);

    requestAs($outsider, 'GET', '/api/projects')
        ->assertOk()
        ->assertJsonMissing(['id' => $owned->id])
        ->assertJsonMissing(['id' => $joined->id]);
});

it('create project and return correct structure', function () {
    $me = User::factory()->create();

    $response = requestAs($me, 'POST', '/api/projects', [
        'name'        => 'My project',
        'description' => 'desc',
    ])->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'description',
                'owner_id',
                'tasks_count',
                'created_at',
                'updated_at',
            ],
        ])->assertJson([
            'data' => [
                'name'     => 'My project',
                'owner_id' => $me->id,
            ],
        ]);

    $id = $response->json('data.id');
    expect($id)->toBeInt();
    assertDatabaseHas('projects', [
        'id'       => $id,
        'owner_id' => $me->id,
    ]);

    requestAs($me, 'POST', '/api/projects', [
        'name'        => '',
        'description' => 'desc',
    ])->assertUnprocessable()
        ->assertJsonStructure(['message', 'errors' => ['name']]);

});

it('project show: member ok, outsider forbidden', function () {
    $ctx = setupProjectWithRoles();

    requestAs(userFor($ctx, 'member'), 'GET', "/api/projects/{$ctx['project']->id}")
        ->assertOk()
        ->assertJsonStructure(['data' => ['id', 'name', 'owner_id', 'tasks_count']]);

    requestAs(userFor($ctx, 'outsider'), 'GET', "/api/projects/{$ctx['project']->id}")
        ->assertForbidden();

    requestAs(userFor($ctx, 'member'), 'GET', '/api/projects/99999')
        ->assertNotFound();
});

it('update: allows owner/admin, denies others, invalid field (422)', function () {
    $ctx = setupProjectWithRoles();
    assertRoleMatrix($ctx, 'PATCH', "/api/projects/{$ctx['project']->id}", [
        'owner'    => 200,
        'admin'    => 200,
        'member'   => 403,
        'viewer'   => 403,
        'outsider' => 403,
    ], ['name' => 'Renamed']);

    assertDatabaseHas('projects', [
        'id'   => $ctx['project']->id,
        'name' => 'Renamed',
    ]);

    requestAs(userFor($ctx, 'owner'), 'PATCH', "/api/projects/{$ctx['project']->id}", [
        'name' => 123,
    ])->assertUnprocessable()
        ->assertJsonStructure(['message', 'errors' => ['name']]);
});

it('delete: only owner', function () {
    $ctx = setupProjectWithRoles();
    assertRoleMatrix($ctx, 'DELETE', "/api/projects/{$ctx['project']->id}", [
        'admin'    => 403,
        'member'   => 403,
        'viewer'   => 403,
        'outsider' => 403,
        'owner'    => 204,
    ], ['name' => 'Renamed']);

    expect($ctx['project']->fresh()->trashed())->toBeTrue();
});

it('transfer owner: only owner, new owner must be a member', function () {
    $ctx      = setupProjectWithRoles();
    $newOwner = userFor($ctx, 'admin');
    requestAs(userFor($ctx, 'owner'), 'POST', "/api/projects/{$ctx['project']->id}/owner", [
        'new_owner_id' => $newOwner->id,
    ])->assertOk();

    expect($ctx['project']->refresh()->owner_id)->toBe($newOwner->id);

    requestAs(userFor($ctx, 'outsider'), 'POST', "/api/projects/{$ctx['project']->id}/owner", [
        'new_owner_id' => $newOwner->id,
    ])->assertForbidden();

    $newOutsider = User::factory()->create();
    requestAs(userFor($ctx, 'owner'), 'POST', "/api/projects/{$ctx['project']->id}/owner", [
        'new_owner_id' => $newOutsider->id,
    ])->assertForbidden();
});
