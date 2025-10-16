<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskCommentStoreRequest;
use App\Http\Resources\TaskCommentResource;
use App\Http\Services\TaskCommentService;
use App\Models\Task;
use Illuminate\Http\JsonResponse;

class TaskCommentController extends Controller
{
    public function store(TaskCommentStoreRequest $request, Task $task, TaskCommentService $service): JsonResponse
    {
        $this->authorize('comment', $task);
        $dto     = $request->toDTO();
        $comment = $service->createComment($dto);

        return TaskCommentResource::make($comment)->response()->setStatusCode(201);
    }
}
