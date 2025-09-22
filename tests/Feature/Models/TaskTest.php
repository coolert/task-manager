<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Label;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskLabel;
use App\Models\User;

it('task relations work', function () {
    $project  = Project::factory()->create();
    $creator  = User::factory()->create();
    $assignee = User::factory()->create();
    $task     = Task::factory()
        ->for($project)
        ->for($creator, 'creator')
        ->for($assignee, 'assignee')
        ->create([
            'status'   => TaskStatus::Doing,
            'priority' => TaskPriority::High,
        ]);

    expect($task->project->is($project))->toBeTrue()
        ->and($task->creator->is($creator))->toBeTrue()
        ->and($task->assignee->is($assignee))->toBeTrue();

    $label = Label::factory()->for($project)->create();
    $task->labels()->attach($label->id);
    $attach = $task->labels()->firstOrFail();
    /** @var TaskLabel $pivot */
    $pivot = $attach->pivot;
    expect($pivot->created_at)->not()->toBeNull();
    expect($pivot->updated_at)->not()->toBeNull();

    TaskComment::factory()->for($task)->count(2)->create();
    expect($task->comments()->count())->toBeGreaterThanOrEqual(2);

    expect($task->status)->toBeInstanceOf(TaskStatus::class)
        ->and($task->priority)->toBeInstanceOf(TaskPriority::class);
});
