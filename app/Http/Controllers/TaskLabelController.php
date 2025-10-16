<?php

namespace App\Http\Controllers;

use App\Http\Resources\TaskResource;
use App\Http\Services\TaskLabelService;
use App\Models\Label;
use App\Models\Task;
use Illuminate\Http\Response;

class TaskLabelController extends Controller
{
    public function attach(Task $task, Label $label, TaskLabelService $service): TaskResource
    {
        $this->authorize('label', $task);
        abort_unless($task->project_id === $label->project_id, 409, 'Label not in this project');
        $task = $service->attachLabel($task, $label);

        return TaskResource::make($task);
    }

    public function detach(Task $task, Label $label, TaskLabelService $service): Response
    {
        $this->authorize('label', $task);
        abort_unless($task->project_id === $label->project_id, 409, 'Label not in this project');
        $service->detachLabel($task, $label);

        return response()->noContent();
    }
}
