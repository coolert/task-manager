<?php

namespace App\DTOs;

class TaskUpdateDTO
{
    /**
     * @param  array<string,mixed>  $payload
     */
    public function __construct(private array $payload) {}

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        if (array_key_exists('order_no', $data) && is_null($data['order_no'])) {
            $data['order_no'] = 0;
        }

        return new self($data);
    }

    /**
     * @return array{
     *     title?: string,
     *     description?: ?string,
     *     status?: string,
     *     priority?: string,
     *     assignee_id?: ?int,
     *     due_date?: ?string,
     *     order_no?: int
     * }
     */
    public function toModelArray(): array
    {
        $out = [];
        $map = [
            'title'       => fn ($v) => (string) $v,
            'description' => fn ($v) => $v,
            'status'      => fn ($v) => (string) $v,
            'priority'    => fn ($v) => (string) $v,
            'assignee_id' => fn ($v) => $v,
            'due_date'    => fn ($v) => $v,
            'order_no'    => fn ($v) => (int) $v,
        ];
        foreach ($map as $key => $cast) {
            if (array_key_exists($key, $this->payload)) {
                $out[$key] = $cast($this->payload[$key]);
            }
        }

        return $out;
    }
}
