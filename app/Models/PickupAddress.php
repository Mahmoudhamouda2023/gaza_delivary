<?php

namespace App\Models;

use GazaDelivery\Foundation\Models\Store;

final class PickupAddress extends DeliveryModel
{
    protected $casts = ['active' => 'boolean'];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function neighborhood()
    {
        return $this->belongsTo(Neighborhood::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
