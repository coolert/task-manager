<?php

namespace App\MessagePipeline\Handlers;

use App\Enums\InboxStatus;
use App\MessagePipeline\Inbox\InboxService;
use App\MessagePipeline\Retry\RetryDecider;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

abstract class BaseHandler
{
    /**
     * @param  array<string, mixed>  $payload
     */
    abstract public function process(array $payload): void;

    protected bool $useInbox = true;

    protected bool $useVersionCheck = true;

    protected bool $useRetry = true;

    protected bool $useParking = true;

    protected int $maxRetryStage = 3;

    public function __construct(
        protected RetryDecider $retry,
        protected InboxService $inbox
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, AMQPMessage $message): void
    {
        $messageId   = $payload['message_id']   ?? null;
        $version     = $payload['version']      ?? null;
        $businessKey = $payload['business_key'] ?? null;

        // retry stage
        $headers = $message->has('application_headers') ? $message->get('application_headers')->getNativeData() : [];

        $xDeath = $headers['x-death'] ?? [];
        $stage  = $this->retry->getRetryStage($xDeath);

        // inbox idempotency check
        if ($this->useInbox && $messageId) {
            if ($this->inbox->alreadyProcessed($messageId)) {
                $this->ack($message);

                return;
            }
        }

        // version check
        if ($this->useVersionCheck && $version && $businessKey) {
            if ($this->inbox->versionOld($version, $businessKey)) {
                $this->inbox->markProcessed($messageId, InboxStatus::SKIPPED, $payload, $version, $businessKey);
                $this->ack($message);

                return;
            }
        }

        try {
            Log::info(static::class . ' processing', [
                'message_id'  => $messageId,
                'payload'     => $payload,
                'retry_stage' => $stage,
            ]);

            $this->process($payload);

            if ($this->useInbox && $messageId) {
                $this->inbox->markProcessed($messageId, InboxStatus::SUCCESS, $payload, $version, $businessKey);
            }

            $this->ack($message);
        } catch (Throwable $e) {
            Log::error(static::class . ' process failed', [
                'error'       => $e->getMessage(),
                'message_id'  => $messageId,
                'payload'     => $payload,
                'retry_stage' => $stage,
            ]);

            if ($stage > $this->maxRetryStage || ! $this->useRetry) {
                $this->sendToParking($message, $payload);

                return;
            }

            $retryExchange = $this->retry->getRetryExchange($stage);
            // retry
            if ($retryExchange) {
                $message->getChannel()->basic_publish($message, $retryExchange, 'retry.task');
                $this->reject($message, false);

                return;
            }

            $this->sendToParking($message, $payload);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function sendToParking(AMQPMessage $message, array $payload): void
    {
        if (! $this->useParking) {
            $this->reject($message, false);

            return;
        }

        $messageId   = $payload['message_id']   ?? null;
        $version     = $payload['version']      ?? null;
        $businessKey = $payload['business_key'] ?? null;

        if ($this->useInbox && $messageId) {
            $this->inbox->markProcessed($messageId, InboxStatus::FAILED, $payload, $version, $businessKey);
        }

        $message->getChannel()->basic_publish($message, 'task.parking.exchange', 'parking.task');
        $this->reject($message, false);
    }

    protected function ack(AMQPMessage $message): void
    {
        $message->getChannel()->basic_ack($message->getDeliveryTag());
    }

    protected function nack(AMQPMessage $message, bool $requeue = false): void
    {
        $message->getChannel()->basic_nack($message->getDeliveryTag(), false, $requeue);
    }

    protected function reject(AMQPMessage $message, bool $requeue = false): void
    {
        $message->getChannel()->basic_reject($message->getDeliveryTag(), $requeue);
    }
}
