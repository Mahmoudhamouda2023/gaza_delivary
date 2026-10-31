<?php

namespace App\Models;

use GazaDelivery\Foundation\Models\Staff;

final class CaptainPayout extends DeliveryModel
{
    public function captain()
    {
        return $this->belongsTo(Staff::class, 'captain_id');
    }
}
