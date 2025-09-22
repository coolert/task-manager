<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;

it('task_comment relations work', function () {
    $project = Project::factory()->create();
    $task    = Task::factory()->for($project)->create();
    $user    = User::factory()->create();

    $comment = TaskComment::factory()->for($task)->for($user)->create();
    expect($comment->task->is($task))->toBeTrue()
        ->and($comment->user->is($user))->toBeTrue();
});
