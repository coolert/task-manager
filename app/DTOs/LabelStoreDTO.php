<?php

namespace App\DTOs;

class LabelStoreDTO
{
    public function __construct(
        public int $projectId,
        public string $name,
        public string $color
    ) {}

    /**
     * @param array{
     *     project_id: int,
     *     name: string,
     *     color: string
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            projectId: $data['project_id'],
            name: $data['name'],
            color: $data['color']
        );
    }

    /**
     * @return array{
     *     project_id: int,
     *     name: string,
     *     color: string
     * }
     */
    public function toModelArray(): array
    {
        return [
            'project_id' => $this->projectId,
            'name'       => $this->name,
            'color'      => $this->color,
        ];
    }
}
