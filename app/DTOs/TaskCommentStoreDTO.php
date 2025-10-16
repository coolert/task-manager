<?php

namespace App\DTOs;

use App\Models\Task;
use App\Models\User;

class TaskCommentStoreDTO
{
    public function __construct(
        public int $taskId,
        public int $userId,
        public string $content
    ) {}

    /**
     * @param array{
     *     content: string
     * }$data
     */
    public static function fromArray(array $data, Task $task, User $user): self
    {
        return new self(
            taskId: $task->id,
            userId: $user->id,
            content: $data['content']
        );
    }

    /**
     * @return array{
     *     content: string
     * }
     */
    public function toModelArray(): array
    {
        return [
            'task_id' => $this->taskId,
            'user_id' => $this->userId,
            'content' => $this->content,
        ];
    }
}
