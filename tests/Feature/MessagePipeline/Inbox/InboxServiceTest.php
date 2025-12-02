<?php

use App\Enums\InboxStatus;
use App\MessagePipeline\Inbox\InboxRecord;
use App\MessagePipeline\Inbox\InboxService;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    $this->service = app(InboxService::class);
});

it('returns true for already processed SUCCESS or SKIPPED', function () {
    $messageId = 'msg-123';

    InboxRecord::create([
        'message_id'   => $messageId,
        'status'       => InboxStatus::SUCCESS,
        'version'      => 1,
        'business_key' => 'task:1',
        'payload'      => ['foo' => 'bar'],
        'processed_at' => now(),
    ]);

    expect($this->service->alreadyProcessed($messageId))->toBeTrue();

    // skipped
    $anotherId = 'msg-456';

    InboxRecord::create([
        'message_id'   => $anotherId,
        'status'       => InboxStatus::SKIPPED,
        'version'      => 1,
        'business_key' => 'task:2',
        'payload'      => ['x' => 1],
        'processed_at' => now(),
    ]);

    expect($this->service->alreadyProcessed($anotherId))->toBeTrue();
});

it('returns false when message not exists or not SUCCESS/SKIPPED', function () {
    $messageId = 'msg-789';

    expect($this->service->alreadyProcessed($messageId))->toBeFalse();

    // failed does not count
    InboxRecord::create([
        'message_id'   => $messageId,
        'status'       => InboxStatus::FAILED,
        'version'      => 1,
        'business_key' => 'task:3',
        'payload'      => ['a' => 1],
        'processed_at' => now(),
    ]);

    expect($this->service->alreadyProcessed($messageId))->toBeFalse();
});

it('returns false when no version is passed', function () {
    expect($this->service->versionOld(null, 'task:1'))->toBeFalse()
        ->and($this->service->versionOld(0, 'task:1'))->toBeFalse();
});

it('returns false when no latest SUCCESS version exists', function () {
    InboxRecord::factory()->create([
        'business_key' => 'task:1',
        'status'       => InboxStatus::FAILED, // FAILED does not count
        'version'      => 5,
    ]);

    expect($this->service->versionOld(3, 'task:1'))->toBeFalse();
});

it('returns true when version is older than latest', function () {
    InboxRecord::factory()->create([
        'business_key' => 'task:1',
        'status'       => InboxStatus::SUCCESS,
        'version'      => 10,
    ]);

    expect($this->service->versionOld(5, 'task:1'))->toBeTrue();
});

it('returns false when version is not older', function () {
    InboxRecord::factory()->create([
        'business_key' => 'task:1',
        'status'       => InboxStatus::SUCCESS,
        'version'      => 10,
    ]);

    expect($this->service->versionOld(10, 'task:1'))->toBeFalse()
        ->and($this->service->versionOld(11, 'task:1'))->toBeFalse();
});

it('creates inbox record when marking processed', function () {
    $messageId = 'msg-999';
    $payload   = ['foo' => 'bar'];

    $this->service->markProcessed(
        $messageId,
        InboxStatus::SUCCESS,
        $payload,
        version: 3,
        businessKey: 'task:123'
    );

    assertDatabaseHas('inbox', [
        'message_id'   => $messageId,
        'status'       => InboxStatus::SUCCESS->value,
        'version'      => 3,
        'business_key' => 'task:123',
    ]);

    $record = InboxRecord::first();
    expect($record->payload)->toBe($payload);
});
