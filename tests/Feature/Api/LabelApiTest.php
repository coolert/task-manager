<?php

use App\Models\Label;
use App\Models\Task;

use function Pest\Laravel\assertDatabaseHas;

it('list labels: project members can see', function () {
    $ctx = setupProjectWithRoles();
    Label::factory()->count(2)->for($ctx['project'])->create();

    requestAs(userFor($ctx, 'member'), 'GET', "/api/projects/{$ctx['project']->id}/labels")
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'name',
                    'color',
                ],
            ],
        ]);
});

it('create label: only owner/admin; project-scoped unique', function () {
    $ctx = setupProjectWithRoles();
    Label::factory()->for($ctx['project'])->create(['name' => 'Bug']);

    $response = requestAs(userFor($ctx, 'owner'), 'POST', "/api/projects/{$ctx['project']->id}/labels", [
        'name'  => 'Label 1',
        'color' => '#00ff00',
    ])->assertCreated();

    $id = $response->json('data.id');
    expect($id)->toBeInt();
    assertDatabaseHas('labels', [
        'id'    => $id,
        'name'  => 'Label 1',
        'color' => '#00ff00',
    ]);

    requestAs(userFor($ctx, 'member'), 'POST', "/api/projects/{$ctx['project']->id}/labels", [
        'name'  => 'Label 2',
        'color' => '#00ff00',
    ])->assertForbidden();

    requestAs(userFor($ctx, 'owner'), 'POST', "/api/projects/{$ctx['project']->id}/labels", [
        'name' => 'Bug', 'color' => '#ff0000',
    ])->assertUnprocessable();
});

it('update label: ignore current name; conflict 422', function () {
    $ctx = setupProjectWithRoles();
    $a   = Label::factory()->for($ctx['project'])->create(['name' => 'A']);
    Label::factory()->for($ctx['project'])->create(['name' => 'B']);

    requestAs(userFor($ctx, 'owner'), 'PATCH', "/api/projects/{$ctx['project']->id}/labels/{$a->id}", [
        'name' => 'A',
    ])->assertOk();

    assertDatabaseHas('labels', [
        'id'   => $a->id,
        'name' => 'A',
    ]);

    requestAs(userFor($ctx, 'owner'), 'PATCH', "/api/projects/{$ctx['project']->id}/labels/{$a->id}", [
        'name' => 'B',
    ])->assertUnprocessable();
});

it('delete label: only owner/admin; forbids deleting used label 409', function () {
    $ctx = setupProjectWithRoles();
    $a   = Label::factory()->for($ctx['project'])->create();

    requestAs($ctx['member'], 'DELETE', "/api/projects/{$ctx['project']->id}/labels/{$a->id}")
        ->assertForbidden();

    requestAs($ctx['owner'], 'DELETE', "/api/projects/{$ctx['project']->id}/labels/{$a->id}")
        ->assertNoContent();

    expect($a->fresh()->trashed())->toBeTrue();

    $b    = Label::factory()->for($ctx['project'])->create();
    $task = Task::factory()->for($ctx['project'])->create();
    $task->labels()->syncWithoutDetaching($b);
    requestAs($ctx['owner'], 'DELETE', "/api/projects/{$ctx['project']->id}/labels/{$b->id}")
        ->assertConflict();
});
