<?php

namespace App\Models;

final class FinancialEntry extends DeliveryModel
{
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function remittance()
    {
        return $this->belongsTo(Remittance::class);
    }

    public function scopePaymentVerified($q)
    {
        return $q->whereHas('order', fn ($o) => $o->where(fn ($b) => $b->whereDoesntHave('payment')->orWhereHas('payment', fn ($p) => $p->where('status', 'approved'))));
    }

    public function scopeCleared($q)
    {
        return $q->paymentVerified()->where(fn ($b) => $b->where('collected_minor', 0)->orWhereHas('remittance', fn ($r) => $r->where('status', 'approved')));
    }
}
