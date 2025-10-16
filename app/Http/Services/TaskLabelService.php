<?php

namespace App\Http\Services;

use App\Models\Label;
use App\Models\Task;

class TaskLabelService
{
    public function attachLabel(Task $task, Label $label): Task
    {
        $task->labels()->syncWithoutDetaching([$label->id]);

        return $task->fresh('labels');
    }

    public function detachLabel(Task $task, Label $label): void
    {
        $task->labels()->detach($label->id);
    }
}
