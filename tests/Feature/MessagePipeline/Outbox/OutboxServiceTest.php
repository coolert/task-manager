<?php

use App\Enums\OutboxStatus;
use App\MessagePipeline\Outbox\OutboxRecord;
use App\MessagePipeline\Outbox\OutboxService;
use Illuminate\Support\Str;

use function Pest\Laravel\assertDatabaseHas;

it('stores a pending outbox record with correct payload structure', function () {
    $service = app(OutboxService::class);

    $data = [
        'task_id' => 42,
        'title'   => 'Test task',
        'user_id' => 1,
    ];

    $topic       = 'task.main.exchange';
    $routingKey  = 'task.created';
    $businessKey = 'task:42';

    $record = $service->store($topic, $routingKey, $data, $businessKey);

    expect($record)->toBeInstanceOf(OutboxRecord::class)
        ->and($record->topic)->toBe($topic)
        ->and($record->routing_key)->toBe($routingKey);

    $payload = $record->payload;

    expect($payload)->toBeArray()
        ->and($payload['event'])->toBe($routingKey)
        ->and($payload['data'])->toBe($data)
        ->and($payload['business_key'])->toBe($businessKey)
        ->and($payload['timestamp'])->toBeInt()
        ->and($payload['version'])->toBe($payload['timestamp'])
        ->and($payload['message_id'])->not->toBeEmpty()
        ->and(Str::isUuid($payload['message_id']))->toBeTrue()
        ->and($record->status)->toBe(OutboxStatus::Pending);

    assertDatabaseHas('outbox', [
        'id'          => $record->id,
        'topic'       => $topic,
        'routing_key' => $routingKey,
    ]);
});
