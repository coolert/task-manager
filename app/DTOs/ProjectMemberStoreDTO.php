<?php

namespace App\DTOs;

class ProjectMemberStoreDTO
{
    public function __construct(
        public int $projectId,
        public int $userId,
        public string $role
    ) {}

    /**
     * @param array{
     *     project_id: int,
     *     user_id: int,
     *     role: string
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            projectId: $data['project_id'],
            userId: $data['user_id'],
            role: $data['role']
        );
    }

    /**
     * @return array{
     *     project_id: int,
     *     user_id: int,
     *     role: string
     * }
     */
    public function toModelArray(): array
    {
        return [
            'project_id' => $this->projectId,
            'user_id'    => $this->userId,
            'role'       => $this->role,
        ];
    }
}
