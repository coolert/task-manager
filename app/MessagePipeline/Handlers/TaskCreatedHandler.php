<?php

namespace App\MessagePipeline\Handlers;

use App\MessagePipeline\Consumer\Attributes\Consumes;
use Illuminate\Support\Facades\Http;

#[Consumes('task.created')]
class TaskCreatedHandler extends BaseHandler
{
    public function process(array $payload): void
    {
        $webhookUrl = config('services.webhooks.task_created');
        Http::post($webhookUrl, $payload);
    }
}
