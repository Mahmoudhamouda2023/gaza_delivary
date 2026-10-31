<?php

namespace App\Models;

use GazaDelivery\Foundation\Models\Store;

final class Subscription extends DeliveryModel
{
    protected $casts = ['starts_at' => 'datetime', 'expires_at' => 'datetime'];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
