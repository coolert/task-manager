<?php

use App\Models\Label;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

it('attach/detach label: same project only; detach idempotent', function () {
    $ctx = setupProjectWithRoles();
    $p1  = $ctx['project'];
    $p2  = Project::factory()->create();

    $task   = Task::factory()->for($p1)->for(userFor($ctx, 'owner'), 'creator')->create();
    $label1 = Label::factory()->for($p1)->create();
    $label2 = Label::factory()->for($p2)->create();

    // attach ok + idempotent
    requestAs(userFor($ctx, 'owner'), 'POST', "/api/tasks/{$task->id}/labels/{$label1->id}")
        ->assertOk();
    assertDatabaseHas('task_labels', [
        'task_id'  => $task->id,
        'label_id' => $label1->id,
    ]);
    expect(
        DB::table('task_labels')->where([
            'task_id'  => $task->id,
            'label_id' => $label1->id,
        ])->count()
    )->toBe(1);

    requestAs(userFor($ctx, 'viewer'), 'POST', "/api/tasks/{$task->id}/labels/{$label1->id}")
        ->assertOk();
    expect(
        DB::table('task_labels')->where([
            'task_id'  => $task->id,
            'label_id' => $label1->id,
        ])->count()
    )->toBe(1);

    // outsider forbidden
    requestAs(userFor($ctx, 'outsider'), 'POST', "/api/tasks/{$task->id}/labels/{$label1->id}")
        ->assertForbidden();

    requestAs(userFor($ctx, 'owner'), 'POST', "/api/tasks/999999/labels/{$label1->id}")
        ->assertNotFound();

    requestAs(userFor($ctx, 'owner'), 'POST', "/api/tasks/{$task->id}/labels/999999")
        ->assertNotFound();

    // attach cross project -> 409
    requestAs(userFor($ctx, 'owner'), 'POST', "/api/tasks/{$task->id}/labels/{$label2->id}")
        ->assertConflict();

    // detach ok + idempotent
    requestAs(userFor($ctx, 'owner'), 'DELETE', "/api/tasks/{$task->id}/labels/{$label1->id}")
        ->assertNoContent();
    requestAs(userFor($ctx, 'owner'), 'DELETE', "/api/tasks/{$task->id}/labels/{$label1->id}")
        ->assertNoContent();
    assertDatabaseMissing('task_labels', [
        'task_id'  => $task->id,
        'label_id' => $label1->id,
    ]);

    requestAs(userFor($ctx, 'owner'), 'DELETE', "/api/tasks/{$task->id}/labels/{$label2->id}")
        ->assertConflict();

    requestAs(userFor($ctx, 'viewer'), 'DELETE', "/api/tasks/{$task->id}/labels/{$label1->id}")
        ->assertNoContent();

    requestAs(userFor($ctx, 'outsider'), 'DELETE', "/api/tasks/{$task->id}/labels/{$label1->id}")
        ->assertForbidden();
});
