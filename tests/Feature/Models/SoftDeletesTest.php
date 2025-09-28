<?php

use App\Models\Label;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;

dataset('softDeletableModels', [
    [User::class, fn () => User::factory()->create()],
    [Project::class, fn () => Project::factory()->create()],
    [Task::class, fn () => Task::factory()->create()],
    [TaskComment::class, fn () => TaskComment::factory()->create()],
    [Label::class, fn () => Label::factory()->create()],
]);

it('soft deletes and restores', function (string $class, Closure $make) {
    $model = $make();
    $id    = $model->getKey();

    $model->delete();
    expect($class::query()->find($id))->toBeNull();

    $trashed = $class::query()->withTrashed()->find($id);
    expect($trashed)->not->toBeNull()
        ->and($trashed->deleted_at)->not->toBeNull();

    $trashed->restore();
    expect($class::query()->find($id))->not->toBeNull();
})->with('softDeletableModels');
