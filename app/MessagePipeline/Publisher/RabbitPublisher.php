<?php

namespace App\MessagePipeline\Publisher;

use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;
use Throwable;

class RabbitPublisher
{
    protected ?AMQPStreamConnection $connection = null;

    protected ?AMQPChannel $channel = null;

    public function __construct()
    {
        $this->connect();
    }

    protected function connect(): void
    {
        if ($this->connection && $this->connection->isConnected() && $this->channel && $this->channel->is_open()) {
            return;
        }

        $cfg = config('queue.connections.rabbitmq');

        try {
            $this->connection = new AMQPStreamConnection(
                $cfg['host'],
                $cfg['port'],
                $cfg['user'],
                $cfg['password'],
                $cfg['vhost'],
            );

            $this->channel = $this->connection->channel();

            $this->channel->confirm_select();

            $this->channel->set_return_listener(function ($replyCode, $replyText, $exchange, $routingKey, $message) {
                Log::error('RabbitMQ returned message', [
                    'code'       => $replyCode,
                    'text'       => $replyText,
                    'exchange'   => $exchange,
                    'routingKey' => $routingKey,
                    'message'    => $message->getBody(),
                ]);
            });

            $this->channel->set_ack_handler(function (AMQPMessage $message) {});

            $this->channel->set_nack_handler(function (AMQPMessage $message) {
                Log::warning('Publisher NACK received', [
                    'message' => $message->getBody(),
                ]);
            });
        } catch (Throwable $e) {
            Log::error('RabbitMQ connection failed', [
                'error' => $e->getMessage(),
            ]);
            $this->reconnect();
            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function publish(string $exchange, string $routingKey, array $payload): void
    {
        try {
            $this->connect();

            $body      = json_encode($payload, JSON_UNESCAPED_UNICODE);
            $messageId = $payload['message_id'];
            $msg       = new AMQPMessage($body, [
                'content_type'  => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'message_id'    => $messageId,
                'timestamp'     => time(),
            ]);

            $this->channel->basic_publish($msg, $exchange, $routingKey, true);

            $this->channel->wait_for_pending_acks_returns();

            if (! $this->connection->isConnected() || ! $this->channel->is_open()) {
                throw new RuntimeException('RabbitMQ silent close detected after publish');
            }
        } catch (Throwable $e) {
            Log::error('RabbitMQ publish failed', [
                'exchange'   => $exchange,
                'routingKey' => $routingKey,
                'error'      => $e->getMessage(),
            ]);
            $this->reconnect();
            throw $e;
        }
    }

    protected function reconnect(): void
    {
        try {
            $this->channel?->close();
            $this->connection?->close();
        } catch (Throwable) {
        }
        $this->connection = null;
        $this->channel    = null;
        $this->connect();
    }
}
