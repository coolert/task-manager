<?php

use App\Enums\ProjectRole;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

it('task view, denies outsider', function () {
    ['project' => $project, 'owner' => $owner, 'admin' => $admin, 'member' => $member, 'viewer' => $viewer, 'outsider' => $outsider] = setupProjectWithRoles();
    $task                                                                                                                            = Task::factory()->for($project)->for($owner, 'creator')->create();

    expect(Gate::forUser($outsider)->denies('view', $task))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('view', $task))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('view', $task))->toBeTrue()
        ->and(Gate::forUser($member)->allows('view', $task))->toBeTrue()
        ->and(Gate::forUser($viewer)->allows('view', $task))->toBeTrue();
});

it('task create, allows owner/admin, denies member/viewer/outsider', function () {
    ['project' => $project, 'owner' => $owner, 'admin' => $admin, 'member' => $member, 'viewer' => $viewer, 'outsider' => $outsider] = setupProjectWithRoles();

    expect(Gate::forUser($owner)->allows('create', [Task::class, $project]))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('create', [Task::class, $project]))->toBeTrue()
        ->and(Gate::forUser($member)->allows('create', [Task::class, $project]))->toBeTrue()
        ->and(Gate::forUser($viewer)->denies('create', [Task::class, $project]))->toBeTrue()
        ->and(Gate::forUser($outsider)->denies('create', [Task::class, $project]))->toBeTrue();
});

it('task update, allows owner/admin/task_creator/task_assignee, denies member/viewer/outsider', function () {
    ['project' => $project, 'owner' => $owner, 'admin' => $admin, 'member' => $member, 'viewer' => $viewer, 'outsider' => $outsider] = setupProjectWithRoles();
    $creator                                                                                                                         = User::factory()->create();
    $assignee                                                                                                                        = User::factory()->create();

    ProjectMember::factory()->for($project)->for($creator)->create(['role' => ProjectRole::Member]);
    ProjectMember::factory()->for($project)->for($assignee)->create(['role' => ProjectRole::Member]);

    $task = Task::factory()->for($project)->for($creator, 'creator')->for($assignee, 'assignee')->create();

    expect(Gate::forUser($owner)->allows('update', $task))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', $task))->toBeTrue()
        ->and(Gate::forUser($member)->denies('update', $task))->toBeTrue()
        ->and(Gate::forUser($viewer)->denies('update', $task))->toBeTrue()
        ->and(Gate::forUser($outsider)->denies('update', $task))->toBeTrue()
        ->and(Gate::forUser($creator)->allows('update', $task))->toBeTrue()
        ->and(Gate::forUser($assignee)->allows('update', $task))->toBeTrue();

});

it('task delete, allows owner/admin/task_creator, denies member/viewer/outsider', function () {
    ['project' => $project, 'owner' => $owner, 'admin' => $admin, 'member' => $member, 'viewer' => $viewer, 'outsider' => $outsider] = setupProjectWithRoles();
    $creator                                                                                                                         = User::factory()->create();

    ProjectMember::factory()->for($project)->for($creator)->create(['role' => ProjectRole::Member]);

    $task = Task::factory()->for($project)->for($creator, 'creator')->create();

    expect(Gate::forUser($owner)->allows('delete', $task))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('delete', $task))->toBeTrue()
        ->and(Gate::forUser($member)->denies('delete', $task))->toBeTrue()
        ->and(Gate::forUser($viewer)->denies('delete', $task))->toBeTrue()
        ->and(Gate::forUser($outsider)->denies('delete', $task))->toBeTrue()
        ->and(Gate::forUser($creator)->allows('delete', $task))->toBeTrue();
});

it('task assign, allows owner/admin, denies member/viewer/outsider', function () {
    ['project' => $project, 'owner' => $owner, 'admin' => $admin, 'member' => $member, 'viewer' => $viewer, 'outsider' => $outsider] = setupProjectWithRoles();
    $task                                                                                                                            = Task::factory()->for($project)->for($owner, 'creator')->create();

    expect(Gate::forUser($owner)->allows('assign', $task))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('assign', $task))->toBeTrue()
        ->and(Gate::forUser($member)->denies('assign', $task))->toBeTrue()
        ->and(Gate::forUser($viewer)->denies('assign', $task))->toBeTrue()
        ->and(Gate::forUser($outsider)->denies('assign', $task))->toBeTrue();
});

it('task claim, allows owner/admin/member claim with non-assignee task, denies viewer/outsider', function () {
    ['project' => $project, 'owner' => $owner, 'admin' => $admin, 'member' => $member, 'viewer' => $viewer, 'outsider' => $outsider] = setupProjectWithRoles();
    $task                                                                                                                            = Task::factory()->for($project)->for($owner, 'creator')->create();

    expect(Gate::forUser($owner)->allows('claim', $task))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('claim', $task))->toBeTrue()
        ->and(Gate::forUser($member)->allows('claim', $task))->toBeTrue()
        ->and(Gate::forUser($viewer)->denies('claim', $task))->toBeTrue()
        ->and(Gate::forUser($outsider)->denies('claim', $task))->toBeTrue();

    $assignee = User::factory()->create();
    ProjectMember::factory()->for($project)->for($assignee)->create(['role' => ProjectRole::Member]);
    $task->forceFill(['assignee_id' => $assignee->id])->save();

    expect(Gate::forUser($owner)->denies('claim', $task))->toBeTrue()
        ->and(Gate::forUser($admin)->denies('claim', $task))->toBeTrue()
        ->and(Gate::forUser($member)->denies('claim', $task))->toBeTrue()
        ->and(Gate::forUser($viewer)->denies('claim', $task))->toBeTrue()
        ->and(Gate::forUser($outsider)->denies('claim', $task))->toBeTrue();
});

it('task comment, denies outsider', function () {
    ['project' => $project, 'owner' => $owner, 'admin' => $admin, 'member' => $member, 'viewer' => $viewer, 'outsider' => $outsider] = setupProjectWithRoles();
    $task                                                                                                                            = Task::factory()->for($project)->for($owner, 'creator')->create();

    expect(Gate::forUser($outsider)->denies('comment', $task))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('comment', $task))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('comment', $task))->toBeTrue()
        ->and(Gate::forUser($member)->allows('comment', $task))->toBeTrue()
        ->and(Gate::forUser($viewer)->allows('comment', $task))->toBeTrue();
});

it('task label, denies outsider', function () {
    ['project' => $project, 'owner' => $owner, 'admin' => $admin, 'member' => $member, 'viewer' => $viewer, 'outsider' => $outsider] = setupProjectWithRoles();
    $task                                                                                                                            = Task::factory()->for($project)->for($owner, 'creator')->create();

    expect(Gate::forUser($outsider)->denies('label', $task))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('label', $task))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('label', $task))->toBeTrue()
        ->and(Gate::forUser($member)->allows('label', $task))->toBeTrue()
        ->and(Gate::forUser($viewer)->allows('label', $task))->toBeTrue();
});
