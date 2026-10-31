<?php

namespace App\Models;

use GazaDelivery\Foundation\Models\Store;

final class DeliveryCredit extends DeliveryModel
{
    protected $casts = ['expires_on' => 'date'];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
