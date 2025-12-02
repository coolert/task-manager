<?php

use App\Enums\InboxStatus;
use App\MessagePipeline\Handlers\BaseHandler;
use App\MessagePipeline\Inbox\InboxService;
use App\MessagePipeline\Retry\RetryDecider;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

beforeEach(function () {
    $this->retry = Mockery::mock(RetryDecider::class);
    $this->inbox = Mockery::mock(InboxService::class);
});

/**
 * Stub handler for testing BaseHandler
 */
class StubHandler extends BaseHandler
{
    public bool $shouldFail = false;

    public function process(array $payload): void
    {
        if ($this->shouldFail) {
            throw new Exception('Process failed');
        }
    }
}

/**
 * @param  array<string, mixed>  $headers
 */
function mockMessage(array $headers = []): AMQPMessage
{
    $channel = Mockery::mock(AMQPChannel::class);
    $msg     = Mockery::mock(AMQPMessage::class);

    $msg->shouldReceive('getChannel')->andReturn($channel);
    $msg->shouldReceive('getDeliveryTag')->andReturn('tag-1');
    $msg->shouldReceive('has')->with('application_headers')->andReturn(! empty($headers));

    if (! empty($headers)) {
        $msg->shouldReceive('get')->with('application_headers')->andReturn(new AMQPTable($headers));
    }

    return $msg;
}

it('ACKs immediately if message was already processed', function () {
    $payload = [
        'message_id'   => 'mid-1',
        'version'      => 10,
        'business_key' => 'task:1',
    ];

    $message = mockMessage();

    $this->retry
        ->shouldReceive('getRetryStage')
        ->andReturn(1);

    $this->inbox
        ->shouldReceive('alreadyProcessed')
        ->with('mid-1')
        ->andReturn(true);

    $message->getChannel()
        ->shouldReceive('basic_ack')
        ->once();

    $handler = new StubHandler($this->retry, $this->inbox);

    $handler->handle($payload, $message);
});

it('marks SKIPPED & ACKs when version is old', function () {
    $payload = [
        'message_id'   => 'mid-1',
        'version'      => 5,
        'business_key' => 'task:1',
    ];

    $message = mockMessage();

    $this->retry
        ->shouldReceive('getRetryStage')
        ->andReturn(1);

    $this->inbox
        ->shouldReceive('alreadyProcessed')
        ->with('mid-1')
        ->andReturn(false);

    $this->inbox
        ->shouldReceive('versionOld')
        ->with(5, 'task:1')
        ->andReturn(true);

    $this->inbox
        ->shouldReceive('markProcessed')
        ->with('mid-1', InboxStatus::SKIPPED, $payload, 5, 'task:1')
        ->once();

    $message->getChannel()->shouldReceive('basic_ack')->once();

    $handler = new StubHandler($this->retry, $this->inbox);

    $handler->handle($payload, $message);
});

it('processes normally, marks SUCCESS and ACKs', function () {
    $payload = [
        'message_id'   => 'mid-1',
        'version'      => 10,
        'business_key' => 'task:1',
    ];

    $message = mockMessage();

    $this->retry->shouldReceive('getRetryStage')->andReturn(1);

    $this->inbox->shouldReceive('alreadyProcessed')->andReturn(false);
    $this->inbox->shouldReceive('versionOld')->andReturn(false);

    $this->inbox
        ->shouldReceive('markProcessed')
        ->with('mid-1', InboxStatus::SUCCESS, $payload, 10, 'task:1')
        ->once();

    $message->getChannel()->shouldReceive('basic_ack')->once();

    $handler = new StubHandler($this->retry, $this->inbox);

    $handler->handle($payload, $message);
});

it('publishes to retry exchange on failure', function () {
    $payload = [
        'message_id'   => 'mid-1',
        'version'      => 10,
        'business_key' => 'task:1',
    ];

    $message = mockMessage(['x-death' => [['queue' => 'task.retry.10s.queue']]]);

    $this->inbox->shouldReceive('alreadyProcessed')->andReturn(false);
    $this->inbox->shouldReceive('versionOld')->andReturn(false);

    $this->retry->shouldReceive('getRetryStage')->andReturn(1);
    $this->retry->shouldReceive('getRetryExchange')->with(1)->andReturn('task.retry.10s.exchange');

    $channel = $message->getChannel();

    $channel->shouldReceive('basic_publish')
        ->with($message, 'task.retry.10s.exchange', 'retry.task')
        ->once();

    $channel->shouldReceive('basic_reject')
        ->with('tag-1', false)
        ->once();

    $handler             = new StubHandler($this->retry, $this->inbox);
    $handler->shouldFail = true;

    $handler->handle($payload, $message);
});

it('sends to parking after max retry', function () {
    $payload = [
        'message_id'   => 'mid-1',
        'version'      => 10,
        'business_key' => 'task:1',
    ];

    $message = mockMessage(['x-death' => [['queue' => 'task.retry.5m.queue']]]);

    $this->inbox->shouldReceive('alreadyProcessed')->andReturn(false);
    $this->inbox->shouldReceive('versionOld')->andReturn(false);

    // stage=4 > maxRetryStage=3
    $this->retry->shouldReceive('getRetryStage')->andReturn(4);

    $channel = $message->getChannel();

    $this->inbox
        ->shouldReceive('markProcessed')
        ->with('mid-1', InboxStatus::FAILED, $payload, 10, 'task:1')
        ->once();

    $channel->shouldReceive('basic_publish')
        ->with($message, 'task.parking.exchange', 'parking.task')
        ->once();

    $channel->shouldReceive('basic_reject')->once();

    $handler             = new StubHandler($this->retry, $this->inbox);
    $handler->shouldFail = true;

    $handler->handle($payload, $message);
});
