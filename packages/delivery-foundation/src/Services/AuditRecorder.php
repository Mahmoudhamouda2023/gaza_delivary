<?php

declare(strict_types=1);

namespace GazaDelivery\Foundation\Services;

use GazaDelivery\Foundation\Models\AccountAuditLog;
use GazaDelivery\Foundation\Models\Staff;
use GazaDelivery\Foundation\Models\Store;
use GazaDelivery\Foundation\Models\User;
use Illuminate\Support\Arr;

final class AuditRecorder
{
    public function record(string $event, Staff|User|Store $subject, ?Staff $actor, array $metadata = []): void
    {
        $subjectKey = match (true) {
            $subject instanceof Staff => 'subject_staff_id',
            $subject instanceof User => 'subject_user_id',
            $subject instanceof Store => 'subject_store_id',
        };

        $log = new AccountAuditLog;
        $log->forceFill([
            'event' => $event,
            'actor_staff_id' => $actor?->getKey(),
            $subjectKey => $subject->getKey(),
            // Never accept arbitrary request payloads, passwords, session IDs or tokens.
            'metadata' => Arr::only($metadata, ['reason', 'from', 'to', 'roles']),
        ])->save();
    }
}
