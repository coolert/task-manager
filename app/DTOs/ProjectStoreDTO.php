<?php

namespace App\DTOs;

final class ProjectStoreDTO
{
    public function __construct(
        public string $name,
        public ?string $description,
        public int $ownerId
    ) {}

    /**
     * @param array{
     *     name: string,
     *     description?: string|null,
     *     owner_id: int
     * } $data
     */
    public static function fromArray(array $data, int $ownerId): self
    {
        return new self(
            name: $data['name'],
            description: $data['description'] ?? null,
            ownerId: $ownerId,
        );
    }

    /**
     * @return array{
     *     name: string,
     *     description: string|null,
     *     owner_id: int
     * }
     */
    public function toModelArray(): array
    {
        return [
            'name'        => $this->name,
            'description' => $this->description,
            'owner_id'    => $this->ownerId,
        ];
    }
}
