<?php

use App\Models\Label;
use App\Models\Project;
use App\Models\Task;

use function Pest\Laravel\assertDatabaseHas;

it('list labels: project members can see', function () {
    $ctx = setupProjectWithRoles();
    Label::factory()->count(2)->for($ctx['project'])->create();
    $anotherProject = Project::factory()->create();
    $outsideLabel   = Label::factory()->for($anotherProject)->create();

    requestAs(userFor($ctx, 'member'), 'GET', "/api/projects/{$ctx['project']->id}/labels")
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'project_id',
                    'name',
                    'color',
                    'created_at',
                    'updated_at',
                ],
            ],
            'meta' => [
                'current_page',
                'total',
            ],
        ])->assertJsonMissing(['id' => $outsideLabel->id]);

    requestAs(userFor($ctx, 'outsider'), 'GET', "/api/projects/{$ctx['project']->id}/labels")
        ->assertForbidden();
});

it('create label: only owner/admin; project-scoped unique 422; same name allowed on another project', function () {
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
        'name'  => 'Bug',
        'color' => '#ff0000',
    ])->assertUnprocessable();

    $another = Project::factory()->create();
    Label::factory()->for($another)->create(['name' => 'Others']);

    requestAs(userFor($ctx, 'admin'), 'POST', "/api/projects/{$ctx['project']->id}/labels", [
        'name'  => 'Others',
        'color' => '#00ff00',
    ])->assertCreated();
});

it('create label: validation 422', function () {
    $ctx = setupProjectWithRoles();

    requestAs(userFor($ctx, 'owner'), 'POST', "/api/projects/{$ctx['project']->id}/labels", [
        'name'  => '',
        'color' => '#zzzzzz',
    ])->assertUnprocessable()
        ->assertJsonStructure(['message', 'errors' => ['name', 'color']]);
});

it('update label: ignore current name; conflict 422', function () {
    $ctx = setupProjectWithRoles();
    $a   = Label::factory()->for($ctx['project'])->create(['name' => 'A']);
    Label::factory()->for($ctx['project'])->create(['name' => 'B']);

    requestAs(userFor($ctx, 'owner'), 'PATCH', "/api/projects/{$ctx['project']->id}/labels/{$a->id}", [
        'name'  => 'A',
        'color' => '#112233',
    ])->assertOk();

    assertDatabaseHas('labels', [
        'id'    => $a->id,
        'name'  => 'A',
        'color' => '#112233',
    ]);

    requestAs(userFor($ctx, 'owner'), 'PATCH', "/api/projects/{$ctx['project']->id}/labels/{$a->id}", [
        'name' => 'B',
    ])->assertUnprocessable();

    requestAs(userFor($ctx, 'admin'), 'PATCH', "/api/projects/{$ctx['project']->id}/labels/{$a->id}", [
        'name' => 'C',
    ])->assertOk();
});

it('update label: validation 422', function () {
    $ctx = setupProjectWithRoles();
    $a   = Label::factory()->for($ctx['project'])->create();

    requestAs(userFor($ctx, 'owner'), 'PATCH', "/api/projects/{$ctx['project']->id}/labels/{$a->id}", [
        'name'  => '',
        'color' => 'not-a-color',
    ])->assertUnprocessable()
        ->assertJsonStructure(['message', 'errors' => ['color']]);
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
    $task->labels()->syncWithoutDetaching([$b->id]);

    requestAs($ctx['owner'], 'DELETE', "/api/projects/{$ctx['project']->id}/labels/{$b->id}")
        ->assertConflict();

    $c = Label::factory()->for($ctx['project'])->create();

    requestAs($ctx['admin'], 'DELETE', "/api/projects/{$ctx['project']->id}/labels/{$c->id}")
        ->assertNoContent();

    expect($c->fresh()->trashed())->toBeTrue();
});

it('update/delete label: 404 when label not in this project', function () {
    $ctx     = setupProjectWithRoles();
    $another = Project::factory()->create();
    $label   = Label::factory()->for($another)->create();

    requestAs(userFor($ctx, 'owner'), 'PATCH', "/api/projects/{$ctx['project']->id}/labels/{$label->id}", [
        'name' => 'X',
    ])->assertNotFound();

    requestAs(userFor($ctx, 'owner'), 'DELETE', "/api/projects/{$ctx['project']->id}/labels/{$label->id}")
        ->assertNotFound();
});
