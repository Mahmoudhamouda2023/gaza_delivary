<?php

namespace App\Models;

final class Tariff extends DeliveryModel
{
    protected $casts = ['active' => 'boolean'];

    public function fromZone()
    {
        return $this->belongsTo(Zone::class, 'from_zone_id');
    }

    public function toZone()
    {
        return $this->belongsTo(Zone::class, 'to_zone_id');
    }
}
