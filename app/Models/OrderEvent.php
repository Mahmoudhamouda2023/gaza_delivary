<?php

namespace App\Models;

final class OrderEvent extends DeliveryModel
{
    public $timestamps = false;

    protected $casts = ['created_at' => 'datetime'];
}
