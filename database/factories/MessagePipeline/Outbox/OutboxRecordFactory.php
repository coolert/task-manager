<?php

namespace Database\Factories\MessagePipeline\Outbox;

use App\Enums\OutboxStatus;
use App\MessagePipeline\Outbox\OutboxRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OutboxRecord>
 */
class OutboxRecordFactory extends Factory
{
    /** @var class-string<OutboxRecord> */
    protected $model = OutboxRecord::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'topic'       => 'task.main.exchange',
            'routing_key' => 'task.created',
            'payload'     => [
                'message_id' => $this->faker->uuid(),
                'event'      => 'task.created',
                'timestamp'  => $this->faker->unixTime(),
                'version'    => 1,
                'data'       => ['foo' => 'bar'],
            ],
            'status'     => OutboxStatus::Pending,
            'attempts'   => 0,
            'last_error' => null,
        ];
    }
}
