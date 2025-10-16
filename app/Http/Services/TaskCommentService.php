<?php

namespace App\Http\Services;

use App\DTOs\TaskCommentStoreDTO;
use App\Models\TaskComment;

class TaskCommentService
{
    public function createComment(TaskCommentStoreDTO $dto): TaskComment
    {
        return TaskComment::query()->create($dto->toModelArray())->load('user:id,name,avatar_url');
    }
}
