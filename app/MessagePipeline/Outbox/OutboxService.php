<?php

namespace App\MessagePipeline\Outbox;

use App\Enums\OutboxStatus;
use Illuminate\Support\Str;

class OutboxService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function store(string $topic, string $routingKey, array $data, ?string $businessKey = null): OutboxRecord
    {
        $messageId = Str::uuid7()->toString();
        $timestamp = now()->timestamp;

        $payload = [
            'message_id'   => $messageId,
            'event'        => $routingKey,
            'timestamp'    => $timestamp,
            'version'      => $timestamp,
            'business_key' => $businessKey,
            'data'         => $data,
        ];

        return OutboxRecord::create([
            'topic'       => $topic,
            'routing_key' => $routingKey,
            'payload'     => $payload,
            'status'      => OutboxStatus::Pending,
        ]);
    }
}
