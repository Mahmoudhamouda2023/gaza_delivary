<?php

namespace App\Models;

final class OrderPayment extends DeliveryModel
{
    protected $casts = ['reviewed_at' => 'datetime'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function getMethodLabelAttribute(): string
    {
        return config('delivery.payment_methods.'.$this->method, $this->method);
    }

    public function getStatusLabelAttribute(): string
    {
        return ['pending' => 'بانتظار التحقق', 'approved' => 'تم التحقق', 'rejected' => 'مرفوض؛ يحتاج تصحيح'][$this->status];
    }
}
