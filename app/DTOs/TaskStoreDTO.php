<?php

namespace App\DTOs;

class TaskStoreDTO
{
    public function __construct(
        public int $projectId,
        public int $creatorId,
        public string $title,
        public ?string $description,
        public string $status,
        public string $priority,
        public ?int $assigneeId,
        public ?string $due_date,
        public int $orderNo
    ) {}

    /**
     * @param array{
     *     project_id: int,
     *     title: string,
     *     description: ?string,
     *     status: ?string,
     *     priority: ?string,
     *     assignee_id: ?int,
     *     due_date: ?string,
     *     order_no: ?int
     * } $data
     */
    public static function fromArray(array $data, int $creatorId): self
    {
        return new self(
            projectId: $data['project_id'],
            creatorId: $creatorId,
            title: $data['title'],
            description: $data['description'] ?? null,
            status: $data['status'],
            priority: $data['priority'],
            assigneeId: $data['assignee_id'] ?? null,
            due_date: $data['due_date']      ?? null,
            orderNo: $data['order_no']       ?? 0
        );
    }

    /**
     * @return array{
     *      project_id: int,
     *      title: string,
     *      description: ?string,
     *      status: string,
     *      priority: string,
     *      assignee_id: ?int,
     *      due_date: string,
     *      order_no: ?int
     * }
     */
    public function toModelArray(): array
    {
        return [
            'project_id'  => $this->projectId,
            'creator_id'  => $this->creatorId,
            'title'       => $this->title,
            'description' => $this->description,
            'status'      => $this->status,
            'priority'    => $this->priority,
            'assignee_id' => $this->assigneeId,
            'due_date'    => $this->due_date,
            'order_no'    => $this->orderNo,
        ];
    }
}
