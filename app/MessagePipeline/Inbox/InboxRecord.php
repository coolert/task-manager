<?php

namespace App\MessagePipeline\Inbox;

use App\Enums\InboxStatus;
use Database\Factories\MessagePipeline\Inbox\InboxRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InboxRecord extends Model
{
    /** @use HasFactory<InboxRecordFactory> */
    use HasFactory;

    protected $table = 'inbox';

    protected $fillable = [
        'message_id',
        'version',
        'business_key',
        'status',
        'payload',
        'processed_at',
    ];

    protected $casts = [
        'status'       => InboxStatus::class,
        'payload'      => 'array',
        'processed_at' => 'datetime',
    ];
}
