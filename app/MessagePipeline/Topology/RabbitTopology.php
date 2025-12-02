<?php

namespace App\MessagePipeline\Topology;

use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Wire\AMQPTable;
use Throwable;

class RabbitTopology
{
    public function setup(): void
    {
        $cfg        = config('queue.connections.rabbitmq');
        $connection = null;
        $channel    = null;

        try {
            $connection = new AMQPStreamConnection(
                $cfg['host'],
                $cfg['port'],
                $cfg['user'],
                $cfg['password'],
                $cfg['vhost']
            );
            $channel = $connection->channel();

            // main exchange
            $channel->exchange_declare('task.main.exchange', 'topic', false, true, false);

            // retry exchange
            $channel->exchange_declare('task.retry.10s.exchange', 'direct', false, true, false);
            $channel->exchange_declare('task.retry.60s.exchange', 'direct', false, true, false);
            $channel->exchange_declare('task.retry.5m.exchange', 'direct', false, true, false);

            // parking exchange
            $channel->exchange_declare('task.parking.exchange', 'direct', false, true, false);

            // main queue
            $channel->queue_declare('task.main.queue', false, true, false, false);
            $channel->queue_bind('task.main.queue', 'task.main.exchange', 'task.created');

            // retry queue 10s
            $channel->queue_declare('task.retry.10s.queue', false, true, false, false, false, new AMQPTable([
                'x-message-ttl'             => 10_000,
                'x-dead-letter-exchange'    => 'task.main.exchange',
                'x-dead-letter-routing-key' => 'task.created',
            ]));
            $channel->queue_bind('task.retry.10s.queue', 'task.retry.10s.exchange', 'retry.task');

            // retry queue 60s
            $channel->queue_declare('task.retry.60s.queue', false, true, false, false, false, new AMQPTable([
                'x-message-ttl'             => 60_000,
                'x-dead-letter-exchange'    => 'task.main.exchange',
                'x-dead-letter-routing-key' => 'task.created',
            ]));
            $channel->queue_bind('task.retry.60s.queue', 'task.retry.60s.exchange', 'retry.task');

            // retry queue 5m
            $channel->queue_declare('task.retry.5m.queue', false, true, false, false, false, new AMQPTable([
                'x-message-ttl'             => 300_000,
                'x-dead-letter-exchange'    => 'task.main.exchange',
                'x-dead-letter-routing-key' => 'task.created',
            ]));
            $channel->queue_bind('task.retry.5m.queue', 'task.retry.5m.exchange', 'retry.task');

            // parking queue
            $channel->queue_declare('task.parking.queue', false, true, false, false);
            $channel->queue_bind('task.parking.queue', 'task.parking.exchange', 'parking.task');

            Log::info('RabbitMQ topology initialized successfully.');
        } catch (Throwable $e) {
            Log::error('RabbitMQ topology initialization failed', [
                'error' => $e->getMessage(),
            ]);
        } finally {
            try {
                $channel?->close();
                $connection?->close();
            } catch (Throwable) {
            }
        }
    }
}
