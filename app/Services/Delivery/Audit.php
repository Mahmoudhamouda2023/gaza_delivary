<?php

namespace App\Services\Delivery;

use App\Models\DeliveryAudit;
use GazaDelivery\Foundation\Models\Staff;
use GazaDelivery\Foundation\Models\User;

final class Audit
{
    public static function log(Staff|User $actor, string $event, string $subject, array $data = []): void
    {
        $r = new DeliveryAudit;
        $r->forceFill(['staff_id' => $actor instanceof Staff ? $actor->id : null, 'user_id' => $actor instanceof User ? $actor->id : null, 'event' => $event, 'subject' => $subject, 'data' => $data])->save();
    }
}
