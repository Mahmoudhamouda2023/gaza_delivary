<?php

namespace App\Models;

use GazaDelivery\Foundation\Models\Staff;

final class Remittance extends DeliveryModel
{
    protected $casts = ['reviewed_at' => 'datetime'];

    public function captain()
    {
        return $this->belongsTo(Staff::class, 'captain_id');
    }
}
