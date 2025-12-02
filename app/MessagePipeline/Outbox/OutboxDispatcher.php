<?php

namespace App\MessagePipeline\Outbox;

use App\Enums\OutboxStatus;
use App\MessagePipeline\Publisher\RabbitPublisher;
use Illuminate\Support\Facades\DB;

class OutboxDispatcher
{
    public function __construct(protected RabbitPublisher $publisher) {}

    public function dispatch(int $limit = 100): void
    {
        $records = OutboxRecord::where('status', OutboxStatus::Pending)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($records as $record) {
            try {
                $this->publisher->publish(
                    $record->topic,
                    $record->routing_key,
                    $record->payload
                );

                $record->update([
                    'status'   => OutboxStatus::Sent,
                    'attempts' => DB::raw('attempts + 1'),
                ]);
            } catch (\Throwable $e) {
                $record->update([
                    'status'     => OutboxStatus::Failed,
                    'last_error' => $e->getMessage(),
                    'attempts'   => DB::raw('attempts + 1'),
                ]);
            }
        }
    }
}
