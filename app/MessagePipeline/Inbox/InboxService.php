<?php

namespace App\MessagePipeline\Inbox;

use App\Enums\InboxStatus;

class InboxService
{
    public function alreadyProcessed(string $messageId): bool
    {
        return InboxRecord::where('message_id', $messageId)
            ->whereIn('status', [InboxStatus::SUCCESS, InboxStatus::SKIPPED])
            ->exists();
    }

    public function versionOld(?int $version, string $businessKey): bool
    {
        if (! $version) {
            return false;
        }

        $lastest = InboxRecord::where('business_key', $businessKey)
            ->where('status', InboxStatus::SUCCESS)
            ->max('version');
        if (! $lastest) {
            return false;
        }

        return $version < $lastest;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function markProcessed(string $messageId, InboxStatus $status, array $payload, ?int $version, ?string $businessKey): void
    {
        InboxRecord::create([
            'message_id'   => $messageId,
            'status'       => $status,
            'version'      => $version,
            'business_key' => $businessKey,
            'payload'      => $payload,
            'processed_at' => now(),
        ]);
    }
}
