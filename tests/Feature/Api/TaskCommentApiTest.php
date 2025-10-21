<?php

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;

use function Pest\Laravel\assertDatabaseHas;

it('project members can comment; outsider forbidden', function () {
    $ctx            = setupProjectWithRoles();
    $task           = Task::factory()->for($ctx['project'])->for(userFor($ctx, 'owner'), 'creator')->create();
    $anotherProject = Project::factory()->for(userFor($ctx, 'owner'), 'owner')->create();
    ProjectMember::factory()->for($anotherProject)->for(userFor($ctx, 'outsider'))->create();

    $before   = $task->comments()->count();
    $response = requestAs(userFor($ctx, 'member'), 'POST', "/api/tasks/{$task->id}/comments", [
        'content' => 'hello',
    ])->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'id',
                'task_id',
                'user' => [
                    'id',
                    'name',
                    'avatar_url',
                ],
                'content',
                'created_at',
                'updated_at',
            ],
        ])->assertJsonPath('data.user.id', userFor($ctx, 'member')->id);

    $id = $response->json('data.id');
    expect($id)->toBeInt();
    assertDatabaseHas('task_comments', [
        'id'      => $id,
        'task_id' => $task->id,
        'user_id' => userFor($ctx, 'member')->id,
        'content' => 'hello',
    ]);

    expect($task->fresh()->comments()->count())->toBe($before + 1);

    requestAs(userFor($ctx, 'viewer'), 'POST', "/api/tasks/{$task->id}/comments", ['content' => 'hi'])
        ->assertCreated();

    assertDatabaseHas('task_comments', [
        'task_id' => $task->id,
        'user_id' => userFor($ctx, 'viewer')->id,
        'content' => 'hi',
    ]);

    requestAs(userFor($ctx, 'member'), 'POST', "/api/tasks/{$task->id}/comments", ['content' => ''])
        ->assertUnprocessable()
        ->assertJsonStructure(['message', 'errors' => ['content']]);

    requestAs(userFor($ctx, 'member'), 'POST', '/api/tasks/99999/comments', ['content' => 'hello'])
        ->assertNotFound();

    requestAs(userFor($ctx, 'outsider'), 'POST', "/api/tasks/{$task->id}/comments", [
        'content' => 'x',
    ])->assertForbidden();
});
