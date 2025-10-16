<?php

namespace App\DTOs;

class ProjectUpdateDTO
{
    /**
     * @param  array<string,mixed>  $payload
     */
    private function __construct(private array $payload) {}

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * @return array{
     *     name?: string,
     *     description?: string|null
     * }
     */
    public function toModelArray(): array
    {
        $out = [];
        $map = [
            'name'        => fn ($v) => (string) $v,
            'description' => fn ($v) => $v,
        ];
        foreach ($map as $key => $cast) {
            if (array_key_exists($key, $this->payload)) {
                $out[$key] = $cast($this->payload[$key]);
            }
        }

        return $out;
    }
}
