<?php

use App\Enums\OutboxStatus;
use App\MessagePipeline\Outbox\OutboxDispatcher;
use App\MessagePipeline\Outbox\OutboxRecord;
use App\MessagePipeline\Publisher\RabbitPublisher;

beforeEach(function () {
    $this->publisher  = Mockery::mock(RabbitPublisher::class);
    $this->dispatcher = new OutboxDispatcher($this->publisher);
});

it('dispatches pending outbox records and marks them as sent', function () {
    $record = OutboxRecord::factory()->create([
        'status'   => OutboxStatus::Pending,
        'attempts' => 0,
    ]);

    $this->publisher
        ->shouldReceive('publish')
        ->with($record->topic, $record->routing_key, $record->payload)
        ->once();

    $this->dispatcher->dispatch();

    $record->refresh();

    expect($record->status)->toBe(OutboxStatus::Sent)
        ->and($record->attempts)->toBe(1);
});

it('marks record as failed when publish throws exception', function () {
    $record = OutboxRecord::factory()->create([
        'status'   => OutboxStatus::Pending,
        'attempts' => 0,
    ]);

    $this->publisher
        ->shouldReceive('publish')
        ->once()
        ->andThrow(new Exception('publish failed'));

    $this->dispatcher->dispatch();

    $record->refresh();

    expect($record->status)->toBe(OutboxStatus::Failed)
        ->and($record->attempts)->toBe(1)
        ->and($record->last_error)->toBe('publish failed');
});

it('honors the dispatch limit parameter', function () {
    $records = OutboxRecord::factory()->count(3)->create([
        'status'   => OutboxStatus::Pending,
        'attempts' => 0,
    ]);

    $this->publisher
        ->shouldReceive('publish')
        ->times(2);

    $this->dispatcher->dispatch(2);

    $records->each->refresh();

    expect($records[0]->status)->toBe(OutboxStatus::Sent)
        ->and($records[1]->status)->toBe(OutboxStatus::Sent)
        ->and($records[2]->status)->toBe(OutboxStatus::Pending);
});
