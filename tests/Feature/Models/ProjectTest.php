<?php

use App\Models\Label;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\User;

it('project relations work', function () {
    $owner   = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();

    expect($project->owner->is($owner))->toBeTrue();

    Label::factory()->for($project)->count(2)->create();
    expect($project->labels()->count())->toBe(2)
        ->and($project->labels->first())->toBeInstanceOf(Label::class);

    Task::factory()->for($project)->count(2)->create();
    expect($project->tasks()->count())->toBe(2)
        ->and($project->tasks()->first())->toBeInstanceOf(Task::class);

    ProjectMember::factory()->for($project)->count(2)->create();
    expect($project->projectMembers()->count())->toBe(2)
        ->and($project->projectMembers()->first())->toBeInstanceOf(ProjectMember::class);
});
