<?php

use App\MessagePipeline\Retry\RetryDecider;

beforeEach(function () {
    $this->decider = new RetryDecider;
});

it('returns stage 1 when x-death is empty', function () {
    expect($this->decider->getRetryStage([]))->toBe(1);
});

it('returns stage 2 when queue is task.retry.10s.queue', function () {
    $xDeath = [
        ['queue' => 'task.retry.10s.queue'],
    ];
    expect($this->decider->getRetryStage($xDeath))->toBe(2);
});

it('returns stage 3 when queue is task.retry.60s.queue', function () {
    $xDeath = [
        ['queue' => 'task.retry.60s.queue'],
    ];
    expect($this->decider->getRetryStage($xDeath))->toBe(3);
});

it('returns stage 4 when queue is task.retry.5m.queue', function () {
    $xDeath = [
        ['queue' => 'task.retry.5m.queue'],
    ];
    expect($this->decider->getRetryStage($xDeath))->toBe(4);
});

it('defaults to stage 1 when queue is unknown', function () {
    $xDeath = [
        ['queue' => 'unknown.queue'],
    ];
    expect($this->decider->getRetryStage($xDeath))->toBe(1);
});

it('returns correct exchange for each stage', function () {
    expect($this->decider->getRetryExchange(1))->toBe('task.retry.10s.exchange')
        ->and($this->decider->getRetryExchange(2))->toBe('task.retry.60s.exchange')
        ->and($this->decider->getRetryExchange(3))->toBe('task.retry.5m.exchange')
        ->and($this->decider->getRetryExchange(4))->toBeNull()
        ->and($this->decider->getRetryExchange(99))->toBeNull();
});
