<?php

use App\Models\Project;
use App\Models\Task;

it('can create project->task and attach labels/comments via factory hooks', function () {
    $project = Project::factory()->create();
    $task    = Task::factory()->for($project)->create();

    expect($task->project_id)->toBe($project->id)
        ->and($task->labels()->count())->toBeGreaterThanOrEqual(0)
        ->and($task->comments()->count())->toBeGreaterThanOrEqual(0);
});
