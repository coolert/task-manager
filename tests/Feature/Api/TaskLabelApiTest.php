<?php

use App\Models\Label;
use App\Models\Project;
use App\Models\Task;

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
    requestAs(userFor($ctx, 'owner'), 'POST', "/api/tasks/{$task->id}/labels/{$label1->id}")
        ->assertOk();

    // attach cross project -> 409
    requestAs(userFor($ctx, 'owner'), 'POST', "/api/tasks/{$task->id}/labels/{$label2->id}")
        ->assertConflict();

    // detach ok + idempotent
    requestAs(userFor($ctx, 'owner'), 'DELETE', "/api/tasks/{$task->id}/labels/{$label1->id}")
        ->assertNoContent();
    requestAs(userFor($ctx, 'owner'), 'DELETE', "/api/tasks/{$task->id}/labels/{$label1->id}")
        ->assertNoContent();
});
