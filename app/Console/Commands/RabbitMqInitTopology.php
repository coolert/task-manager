<?php

namespace App\Console\Commands;

use App\MessagePipeline\Topology\RabbitTopology;
use Illuminate\Console\Command;

class RabbitMqInitTopology extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mq:init-topology';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize RabbitMQ exchanges, queues and bindings for Task Manager';

    /**
     * Execute the console command.
     */
    public function handle(RabbitTopology $topology): void
    {
        $topology->setup();
        $this->info('RabbitMQ topology initialized.');
    }
}
