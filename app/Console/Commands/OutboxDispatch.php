<?php

namespace App\Console\Commands;

use App\MessagePipeline\Outbox\OutboxDispatcher;
use Illuminate\Console\Command;

class OutboxDispatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'outbox:dispatch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch pending outbox messages to RabbitMQ';

    /**
     * Execute the console command.
     */
    public function handle(OutboxDispatcher $dispatcher): void
    {
        $dispatcher->dispatch();

        $this->info('Outbox messages dispatched.');
    }
}
