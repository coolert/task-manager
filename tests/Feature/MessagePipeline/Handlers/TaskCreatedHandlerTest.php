<?php

use App\MessagePipeline\Handlers\TaskCreatedHandler;
use App\MessagePipeline\Inbox\InboxService;
use App\MessagePipeline\Retry\RetryDecider;
use Illuminate\Support\Facades\Http;

it('sends webhook with correct URL and payload', function () {
    Http::fake();

    config()->set('services.webhooks.task_created', 'http://example.com/webhook');

    $retry = Mockery::mock(RetryDecider::class);
    $inbox = Mockery::mock(InboxService::class);

    $handler = new TaskCreatedHandler($retry, $inbox);

    $payload = [
        'message_id' => '123',
        'data'       => ['foo' => 'bar'],
    ];

    $handler->process($payload);

    Http::assertSent(function ($request) use ($payload) {
        return $request->url() === 'http://example.com/webhook' && $request->data() == $payload;
    });
});
