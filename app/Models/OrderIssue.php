<?php

namespace App\Models;

final class OrderIssue extends DeliveryModel
{
    protected $casts = ['resolved_at' => 'datetime'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
