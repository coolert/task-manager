<?php

namespace App\MessagePipeline\Consumer;

use App\MessagePipeline\Handlers\BaseHandler;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPChannelClosedException;
use PhpAmqpLib\Exception\AMQPConnectionClosedException;
use PhpAmqpLib\Exception\AMQPIOException;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;
use Throwable;

class WorkerProcess
{
    /** @var string[] */
    protected array $queues;

    protected int $memoryLimit;

    protected ?AMQPStreamConnection $connection = null;

    protected ?AMQPChannel $channel = null;

    protected bool $shouldQuit = false;

    /**
     * @param  string[]  $queues
     */
    public function __construct(array $queues, int $memoryLimit)
    {
        $this->queues      = $queues;
        $this->memoryLimit = $memoryLimit;
    }

    public function run(): void
    {
        $this->setupSignalHandlers();
        $retryDelay = 1;

        while (! $this->shouldQuit) {
            try {
                $this->connect();
                $this->consumeLoop();

                $this->closeConnection();
                $retryDelay = 1;
            } catch (Throwable $e) {
                Log::warning('RabbitMQ worker recoverable error, will reconnect', [
                    'error' => $e->getMessage(),
                    'delay' => $retryDelay,
                ]);

                $this->closeConnection();

                // avoid frequent reconnection
                sleep($retryDelay);
                $retryDelay = min($retryDelay * 2, 30);
            }

            if ($this->memoryExceeded()) {
                Log::warning('RabbitMQ worker exiting due to memory limit.', [
                    'memory' => memory_get_usage(true),
                    'limit'  => $this->memoryLimit,
                ]);
                break;
            }
        }

        $this->closeConnection();
        Log::info('RabbitMQ worker stopped gracefully.');
    }

    protected function setupSignalHandlers(): void
    {
        if (! function_exists('pcntl_signal')) {
            return;
        }

        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
        }

        pcntl_signal(SIGTERM, function () {
            $this->shouldQuit = true;
        });

        pcntl_signal(SIGINT, function () {
            $this->shouldQuit = true;
        });
    }

    protected function connect(): void
    {
        if ($this->connection && $this->connection->isConnected() && $this->channel && $this->channel->is_open()) {
            if (! $this->channel->is_consuming()) {
                Log::warning('Channel is open but not consuming. Re-registering consumer...', [
                    'queue' => $this->queues,
                ]);

                foreach ($this->queues as $queue) {
                    $this->channel->basic_consume($queue, '', false, false, false, false, function (AMQPMessage $message) {
                        $this->handleMessage($message);
                    });
                }
            }

            return;
        }

        $cfg = config('queue.connections.rabbitmq');

        $this->connection = new AMQPStreamConnection(
            $cfg['host'],
            $cfg['port'],
            $cfg['user'],
            $cfg['password'],
            $cfg['vhost']
        );

        $this->channel = $this->connection->channel();

        $this->channel->basic_qos(0, 1, false);

        foreach ($this->queues as $queue) {
            $this->channel->basic_consume($queue, '', false, false, false, false, function (AMQPMessage $message) {
                $this->handleMessage($message);
            });
        }

        Log::info('RabbitMQ worker connected and consuming.', [
            'queue' => $this->queues,
        ]);
    }

    protected function handleMessage(AMQPMessage $message): void
    {
        try {
            $body    = $message->getBody();
            $payload = json_decode($body, true) ?? [];

            $event = $payload['event'] ?? $message->getRoutingKey();

            $handlerClass = ConsumerRegistry::resolve($event);

            if (! $handlerClass) {
                Log::warning('No consumer handler found for event.', [
                    'event'       => $event,
                    'routing_key' => $message->getRoutingKey(),
                    'body'        => $body,
                ]);

                $message->getChannel()->basic_ack($message->getDeliveryTag());

                return;
            }

            /** @var BaseHandler $consumer */
            $consumer = app($handlerClass);
            $consumer->handle($payload, $message);
        } catch (Throwable $e) {
            Log::error('RabbitMQ worker handleMessage failed.', [
                'error' => $e->getMessage(),
                'body'  => $message->getBody(),
            ]);

            try {
                $message->getChannel()->basic_nack($message->getDeliveryTag(), false, false);
            } catch (Throwable) {
            }
        }
    }

    protected function consumeLoop(): void
    {
        if (! $this->channel) {
            return;
        }

        while (! $this->shouldQuit && $this->channel->is_consuming()) {
            try {
                $this->channel->wait(null, false, 5);
            } catch (AMQPTimeoutException) {
                // timeout, continue
            } catch (AMQPConnectionClosedException|AMQPChannelClosedException|AMQPIOException $e) {
                Log::warning('AMQP connection/channel closed in consume loop.', [
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            } catch (Throwable $e) {
                Log::error('Unexpected exception in consume loop.', [
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }

            // detect silent close
            if (! $this->connection->isConnected() || ! $this->channel->is_open()) {
                Log::warning('Silent close detected. Reconnecting...', [
                    'connection' => $this->connection?->isConnected(),
                    'channel'    => $this->channel->is_open(),
                ]);

                throw new RuntimeException('Silent close detected');
            }
            if ($this->memoryExceeded()) {
                $this->shouldQuit = true;
                break;
            }
        }
    }

    protected function memoryExceeded(): bool
    {
        return memory_get_usage(true) >= $this->memoryLimit;
    }

    protected function closeConnection(): void
    {
        try {
            if ($this->channel && $this->channel->is_open()) {
                $this->channel->close();
            }
        } catch (Throwable) {
        } finally {
            $this->channel = null;
        }

        try {
            if ($this->connection && $this->connection->isConnected()) {
                $this->connection->close();
            }
        } catch (Throwable) {
        } finally {
            $this->connection = null;
        }
    }
}
