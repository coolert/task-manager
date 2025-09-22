<?php

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;

it('project_member relations work', function () {
    $project       = Project::factory()->create();
    $user          = User::factory()->create();
    $projectMember = ProjectMember::factory()
        ->for($project)
        ->for($user)
        ->create([
            'role' => ProjectRole::Member,
        ]);

    expect($projectMember->project->is($project))->toBeTrue()
        ->and($projectMember->user->is($user))->toBeTrue()
        ->and($projectMember->role)->toBeInstanceOf(ProjectRole::class);

    expect(ProjectMember::query()->count())->toBe(1);
});
