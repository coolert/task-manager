<?php

namespace App\MessagePipeline\Retry;

class RetryDecider
{
    /**
     * @param  array<int, array<string, mixed>>  $xDeathHeaders
     */
    public function getRetryStage(array $xDeathHeaders): int
    {
        if (empty($xDeathHeaders)) {
            return 1;
        }

        $queue = $xDeathHeaders[0]['queue'] ?? '';

        return match ($queue) {
            'task.retry.10s.queue' => 2,
            'task.retry.60s.queue' => 3,
            'task.retry.5m.queue'  => 4,
            default                => 1,
        };
    }

    public function getRetryExchange(int $stage): ?string
    {
        return match ($stage) {
            1       => 'task.retry.10s.exchange',
            2       => 'task.retry.60s.exchange',
            3       => 'task.retry.5m.exchange',
            default => null,
        };
    }
}
