<?php

namespace App\Models;

final class Neighborhood extends DeliveryModel
{
    protected $casts = ['active' => 'boolean'];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }
}
