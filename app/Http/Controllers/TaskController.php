<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskAssignRequest;
use App\Http\Requests\TaskStoreRequest;
use App\Http\Requests\TaskUpdateRequest;
use App\Http\Resources\TaskResource;
use App\Http\Services\TaskService;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TaskController extends Controller
{
    public function index(Project $project, TaskService $service): AnonymousResourceCollection
    {
        $this->authorize('view', $project);
        $tasks = $service->getTasks($project);

        return TaskResource::collection($tasks);
    }

    public function store(TaskStoreRequest $request, Project $project, TaskService $service): JsonResponse
    {
        $this->authorize('create', [Task::class, $project]);
        $dto  = $request->toDTO();
        $task = $service->createTask($dto);

        return TaskResource::make($task)->response()
            ->setStatusCode(201);
    }

    public function show(Task $task, TaskService $service): TaskResource
    {
        $this->authorize('view', $task);
        $task = $service->getTask($task);

        return TaskResource::make($task);
    }

    public function update(TaskUpdateRequest $request, Task $task, TaskService $service): TaskResource
    {
        $this->authorize('update', $task);
        $dto  = $request->toDTO();
        $task = $service->updateTask($dto, $task);

        return TaskResource::make($task);
    }

    public function destroy(Task $task, TaskService $service): Response
    {
        $this->authorize('delete', $task);
        $service->deleteTask($task);

        return response()->noContent();
    }

    public function assign(TaskAssignRequest $request, Task $task, TaskService $service): TaskResource
    {
        $this->authorize('assign', $task);
        $assigneeId = $request->validated('assignee_id');
        $task       = $service->assignTask($task, $assigneeId);

        return TaskResource::make($task);
    }

    public function claim(Request $request, Task $task, TaskService $service): TaskResource
    {
        $this->authorize('claim', $task);
        $assigneeId = $request->user()->id;
        $task       = $service->claimTask($task, $assigneeId);

        return TaskResource::make($task);
    }
}
