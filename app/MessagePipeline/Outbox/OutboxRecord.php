<?php

namespace App\MessagePipeline\Outbox;

use App\Enums\OutboxStatus;
use Database\Factories\MessagePipeline\Outbox\OutboxRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutboxRecord extends Model
{
    /** @use HasFactory<OutboxRecordFactory> */
    use HasFactory;

    protected $table = 'outbox';

    protected $fillable = [
        'topic',
        'routing_key',
        'payload',
        'status',
        'attempts',
        'last_error',
    ];

    protected $casts = [
        'payload' => 'array',
        'status'  => OutboxStatus::class,
    ];
}
