<?php

namespace App\Models;

final class Coupon extends DeliveryModel
{
    protected $casts = ['active' => 'boolean', 'starts_on' => 'date', 'ends_on' => 'date'];
}
