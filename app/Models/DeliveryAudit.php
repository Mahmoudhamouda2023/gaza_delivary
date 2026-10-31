<?php

namespace App\Models;

final class DeliveryAudit extends DeliveryModel
{
    public $timestamps = false;

    protected $casts = ['data' => 'array', 'created_at' => 'datetime'];
}
