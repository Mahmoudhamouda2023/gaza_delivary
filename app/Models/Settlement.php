<?php

namespace App\Models;

use GazaDelivery\Foundation\Models\Store;

final class Settlement extends DeliveryModel
{
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
