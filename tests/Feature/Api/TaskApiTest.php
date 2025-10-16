<?php

use App\Enums\ProjectRole;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;

it('index tasks: project members can see, structure ok', function () {
    $ctx = setupProjectWithRoles();
    Task::factory()->count(2)->for($ctx['project'])->for(userFor($ctx, 'owner'), 'creator')->create();

    requestAs(userFor($ctx, 'member'), 'GET', "/api/projects/{$ctx['project']->id}/tasks")
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'title', 'status', 'priority', 'labels', 'assignee', 'creator', 'created_at', 'updated_at']],
            'meta' => ['current_page', 'total'],
        ]);
});

it('create task: owner/admin/member ok; outsider forbidden; assignee must be non-viewer member', function () {
    $ctx    = setupProjectWithRoles();
    $member = userFor($ctx, 'member');
    $viewer = userFor($ctx, 'viewer');

    $response = requestAs(userFor($ctx, 'owner'), 'POST', "/api/projects/{$ctx['project']->id}/tasks", [
        'title'       => 'T1',
        'assignee_id' => $member->id,
        'status'      => TaskStatus::Todo->value,
        'priority'    => TaskPriority::Normal->value,
    ])->assertCreated();

    $id = $response->json('data.id');
    expect($id)->toBeInt();
    assertDatabaseHas('tasks', [
        'id'    => $id,
        'title' => 'T1',
    ]);

    // viewer as assignee -> 422
    requestAs(userFor($ctx, 'owner'), 'POST', "/api/projects/{$ctx['project']->id}/tasks", [
        'title'       => 'T2',
        'assignee_id' => $viewer->id,
    ])->assertUnprocessable();

    // outsider -> 403
    requestAs(userFor($ctx, 'outsider'), 'POST', "/api/projects/{$ctx['project']->id}/tasks", [
        'title' => 'T3',
    ])->assertForbidden();
});

it('update task: owner/admin/creator/assignee ok; others denied', function () {
    $ctx      = setupProjectWithRoles();
    $creator  = userFor($ctx, 'member');
    $assignee = User::factory()->create();
    ProjectMember::factory()->for($ctx['project'])->for($assignee)->create(['role' => ProjectRole::Member]);
    $task = Task::factory()
        ->for($ctx['project'])
        ->for($creator, 'creator')
        ->for($assignee, 'assignee')
        ->create();

    $uri = "/api/tasks/{$task->id}";
    requestAs(userFor($ctx, 'owner'), 'PATCH', $uri, ['title' => 'R'])->assertOk();

    assertDatabaseHas('tasks', [
        'id'    => $task->id,
        'title' => 'R',
    ]);

    requestAs(userFor($ctx, 'admin'), 'PATCH', $uri, ['title' => 'R'])->assertOk();
    requestAs($creator, 'PATCH', $uri, ['title' => 'R'])->assertOk();
    requestAs($assignee, 'PATCH', $uri, ['title' => 'R'])->assertOk();

    requestAs(userFor($ctx, 'viewer'), 'PATCH', $uri, ['title' => 'R'])->assertForbidden();
    requestAs(userFor($ctx, 'outsider'), 'PATCH', $uri, ['title' => 'R'])->assertForbidden();
});

it('delete task: owner/admin/creator ok; others denied', function () {
    $ctx     = setupProjectWithRoles();
    $creator = userFor($ctx, 'member');
    $task    = Task::factory()->for($ctx['project'])->for($creator, 'creator')->create();
    $uri     = "/api/tasks/{$task->id}";

    requestAs(userFor($ctx, 'owner'), 'DELETE', $uri)->assertNoContent();

    expect($task->fresh()->trashed())->toBeTrue();

    $task = Task::factory()->for($ctx['project'])->for($creator, 'creator')->create();
    requestAs(userFor($ctx, 'admin'), 'DELETE', "/api/tasks/{$task->id}")->assertNoContent();
    $task = Task::factory()->for($ctx['project'])->for($creator, 'creator')->create();
    requestAs($creator, 'DELETE', "/api/tasks/{$task->id}")->assertNoContent();

    $task = Task::factory()->for($ctx['project'])->for($creator, 'creator')->create();
    requestAs(userFor($ctx, 'viewer'), 'DELETE', "/api/tasks/{$task->id}")->assertForbidden();
});

it('assign task: owner/admin ok, assignee must be project member other than viewer', function () {
    $ctx    = setupProjectWithRoles();
    $member = userFor($ctx, 'member');
    $viewer = userFor($ctx, 'viewer');
    $task   = Task::factory()->for($ctx['project'])->for(userFor($ctx, 'owner'), 'creator')->create();

    requestAs(userFor($ctx, 'owner'), 'POST', "/api/tasks/{$task->id}/assign", ['assignee_id' => $member->id])
        ->assertOk()
        ->assertJsonPath('data.assignee.id', $member->id);

    requestAs(userFor($ctx, 'owner'), 'POST', "/api/tasks/{$task->id}/assign", ['assignee_id' => $viewer->id])
        ->assertUnprocessable();

    requestAs(userFor($ctx, 'outsider'), 'POST', "/api/tasks/{$task->id}/assign", ['assignee_id' => $member->id])
        ->assertForbidden();
});

it('claim task: owner/admin/member ok, assignee_id must be null before claim', function () {
    $ctx = setupProjectWithRoles();
    $a   = Task::factory()->for($ctx['project'])->for(userFor($ctx, 'owner'), 'creator')->create();
    $b   = Task::factory()->for($ctx['project'])->for(userFor($ctx, 'owner'), 'creator')->create();
    $c   = Task::factory()->for($ctx['project'])->for(userFor($ctx, 'owner'), 'creator')->for(userFor($ctx, 'admin'), 'assignee')->create();

    requestAs(userFor($ctx, 'viewer'), 'POST', "/api/tasks/{$a->id}/claim")
        ->assertForbidden();

    requestAs(userFor($ctx, 'member'), 'POST', "/api/tasks/{$a->id}/claim")
        ->assertOk();
    requestAs(userFor($ctx, 'owner'), 'POST', "/api/tasks/{$b->id}/claim")
        ->assertOk();

    assertDatabaseHas('tasks', [
        'id'          => $b->id,
        'assignee_id' => userFor($ctx, 'owner')->id,
    ]);

    // assignee_id not null
    requestAs(userFor($ctx, 'member'), 'POST', "/api/tasks/{$c->id}/claim")
        ->assertForbidden();
});
