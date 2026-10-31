<?php

namespace App\Models;

use GazaDelivery\Foundation\Models\Staff;
use GazaDelivery\Foundation\Models\Store;
use Illuminate\Support\Str;

final class Order extends DeliveryModel
{
    protected $casts = ['benefit_reserved' => 'boolean', 'delivered_at' => 'datetime', 'scheduled_for' => 'datetime'];

    protected static function booted(): void
    {
        self::creating(function (self $o) {
            $o->public_id ??= (string) Str::ulid();
            $o->tracking_token ??= bin2hex(random_bytes(24));
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function captain()
    {
        return $this->belongsTo(Staff::class, 'captain_id');
    }

    public function events()
    {
        return $this->hasMany(OrderEvent::class)->orderBy('id');
    }

    public function issues()
    {
        return $this->hasMany(OrderIssue::class)->latest('id');
    }

    public function entry()
    {
        return $this->hasOne(FinancialEntry::class);
    }

    public function payment()
    {
        return $this->hasOne(OrderPayment::class);
    }

    public function getDeliveryTimeLabelAttribute(): string
    {
        return $this->scheduled_for ? $this->scheduled_for->timezone('Asia/Hebron')->format('Y/m/d · H:i').' (توقيت فلسطين)' : 'حالا';
    }

    public function getLabelAttribute(): string
    {
        return config('delivery.order_statuses.'.$this->status, $this->status);
    }
}
