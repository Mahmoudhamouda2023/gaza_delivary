<?php

namespace App\Models;

use GazaDelivery\Foundation\Models\Store;

final class StoreAgreement extends DeliveryModel
{
    protected $casts = ['active' => 'boolean', 'starts_on' => 'date', 'ends_on' => 'date'];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
