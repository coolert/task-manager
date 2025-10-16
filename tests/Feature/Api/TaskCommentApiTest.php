<?php

use App\Models\Task;

it('project members can comment; outsider forbidden', function () {
    $ctx  = setupProjectWithRoles();
    $task = Task::factory()->for($ctx['project'])->for(userFor($ctx, 'owner'), 'creator')->create();

    requestAs(userFor($ctx, 'member'), 'POST', "/api/tasks/{$task->id}/comments", [
        'content' => 'hello',
    ])->assertCreated();

    requestAs(userFor($ctx, 'outsider'), 'POST', "/api/tasks/{$task->id}/comments", [
        'content' => 'x',
    ])->assertForbidden();
});
