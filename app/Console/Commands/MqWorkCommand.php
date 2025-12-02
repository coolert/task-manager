<?php

namespace App\Console\Commands;

use App\MessagePipeline\Consumer\WorkerProcess;
use Illuminate\Console\Command;

class MqWorkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mq:work';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start a RabbitMQ worker for consuming messages';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $group = 'default';

        $cfg = config("message_pipeline.workers.$group");

        $queues      = $cfg['queues']       ?? [];
        $memoryLimit = $cfg['memory_limit'] ?? (128 * 1024 * 1024);

        if (empty($queues)) {
            $this->error("No queues configured for worker group [$group].");

            return;
        }

        $this->info('Starting worker for queues:');
        foreach ($queues as $q) {
            $this->line(" - $q");
        }

        $worker = new WorkerProcess($queues, $memoryLimit);
        $worker->run();
    }
}
