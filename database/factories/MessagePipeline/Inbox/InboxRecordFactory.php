<?php

namespace Database\Factories\MessagePipeline\Inbox;

use App\Enums\InboxStatus;
use App\MessagePipeline\Inbox\InboxRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InboxRecord>
 */
class InboxRecordFactory extends Factory
{
    /** @var class-string<InboxRecord> */
    protected $model = InboxRecord::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'message_id'   => $this->faker->uuid(),
            'status'       => InboxStatus::SUCCESS,
            'version'      => 1,
            'business_key' => 'task:1',
            'payload'      => ['foo' => 'bar'],
            'processed_at' => now(),
        ];
    }
}
