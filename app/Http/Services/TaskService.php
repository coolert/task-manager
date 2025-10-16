<?php

namespace App\Http\Services;

use App\DTOs\TaskStoreDTO;
use App\DTOs\TaskUpdateDTO;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Pagination\LengthAwarePaginator;

class TaskService
{
    /**
     * @return LengthAwarePaginator<int, Task>
     */
    public function getTasks(Project $project): LengthAwarePaginator
    {
        return Task::query()
            ->where('project_id', $project->id)
            ->with([
                'creator:id,name,avatar_url',
                'assignee:id,name,avatar_url',
                'labels:id,project_id,name,color,created_at,updated_at',
            ])
            ->latest('id')
            ->paginate(20);
    }

    public function createTask(TaskStoreDTO $dto): Task
    {
        return Task::query()
            ->create($dto->toModelArray())
            ->load([
                'creator:id,name,avatar_url',
                'assignee:id,name,avatar_url',
                'labels:id,project_id,name,color,created_at,updated_at',
            ]);
    }

    public function getTask(Task $task): Task
    {
        return $task->load([
            'creator:id,name,avatar_url',
            'assignee:id,name,avatar_url',
            'labels:id,project_id,name,color,created_at,updated_at',
        ]);
    }

    public function updateTask(TaskUpdateDTO $dto, Task $task): Task
    {
        $task->fill($dto->toModelArray())->save();

        return $task->fresh([
            'creator:id,name,avatar_url',
            'assignee:id,name,avatar_url',
            'labels:id,project_id,name,color,created_at,updated_at',
        ]);
    }

    public function deleteTask(Task $task): void
    {
        $task->delete();
    }

    public function assignTask(Task $task, int $assigneeId): Task
    {
        $task->fill(['assignee_id' => $assigneeId])->save();

        return $task->fresh([
            'creator:id,name,avatar_url',
            'assignee:id,name,avatar_url',
            'labels:id,project_id,name,color,created_at,updated_at',
        ]);
    }

    public function claimTask(Task $task, int $assigneeId): Task
    {
        $task->fill(['assignee_id' => $assigneeId])->save();

        return $task->fresh([
            'creator:id,name,avatar_url',
            'assignee:id,name,avatar_url',
            'labels:id,project_id,name,color,created_at,updated_at',
        ]);
    }
}
