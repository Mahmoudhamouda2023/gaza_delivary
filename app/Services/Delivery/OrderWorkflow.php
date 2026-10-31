<?php

namespace App\Services\Delivery;

use App\Models\CouponRedemption;
use App\Models\DeliveryCredit;
use App\Models\FinancialEntry;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\Subscription;
use GazaDelivery\Foundation\Models\Staff;
use GazaDelivery\Foundation\Models\Store;
use GazaDelivery\Foundation\Models\User;
use GazaDelivery\Foundation\Support\PhoneNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class OrderWorkflow
{
    public function create(User $actor, array $data): Order
    {
        return DB::transaction(function () use ($actor, $data) {
            $store = Store::where('owner_user_id', $actor->id)->lockForUpdate()->firstOrFail();
            abort_unless($actor->fresh()->isActive() && $store->status->value === 'active', 403);
            if ($old = Order::where('store_id', $store->id)->where('idempotency_key', $data['idempotency_key'])->first()) {
                return $old;
            }
            $schedule = OrderSchedule::attributes($data);
            $price = app(Pricing::class)->quote($store, $data);
            if (isset($data['quote_hash']) && ! hash_equals(Pricing::fingerprint($price), $data['quote_hash'])) {
                $this->fail('price', 'تغيّر السعر أو شروط التوصيل. ارجع إلى نموذج الطلب وراجع السعر من جديد.');
            }
            if (isset($data['expected_fee']) && (int) $data['expected_fee'] !== $price['fee_minor']) {
                $this->fail('price', 'تغيّر السعر أو رصيد المزايا. راجع السعر من جديد قبل التأكيد.');
            }
            if ($price['delivery_credit_id']) {
                DeliveryCredit::whereKey($price['delivery_credit_id'])->decrement('remaining');
            }
            if ($price['subscription_id']) {
                Subscription::whereKey($price['subscription_id'])->decrement('remaining_deliveries');
            }
            $order = new Order;
            $order->forceFill($price + $schedule + [
                'store_id' => $store->id,
                'idempotency_key' => $data['idempotency_key'],
                'to_zone_id' => $data['to_zone_id'],
                'recipient_name' => $data['recipient_name'],
                'recipient_phone' => PhoneNumber::normalize($data['recipient_phone']),
                'recipient_address' => $data['recipient_address'],
                'description' => $data['description'],
                'merchant_reference' => $data['merchant_reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'fee_payer' => $data['fee_payer'],
                'status' => 'created',
            ])->save();
            if ($price['coupon_id']) {
                $r = new CouponRedemption;
                $r->forceFill(['coupon_id' => $price['coupon_id'], 'store_id' => $store->id, 'order_id' => $order->id])->save();
            }
            $this->event($order, $actor, 'تم إنشاء الطلب');

            return $order;
        }, 3);
    }

    public function assign(Staff $actor, Order $order, int $captainId): Order
    {
        abort_unless($actor->isActive() && $actor->hasAnyRole(['super_admin', 'operations']), 403);

        return DB::transaction(function () use ($actor, $order, $captainId) {
            $o = Order::lockForUpdate()->findOrFail($order->id);
            if (! in_array($o->status, ['created', 'assigned'], true)) {
                $this->fail('status', 'الإسناد متاح قبل استلام الطرد فقط.');
            }
            $captain = Staff::whereKey($captainId)->lockForUpdate()->firstOrFail();
            if (! $captain->isActive() || ! $captain->hasRole('captain') || ! $captain->captainProfile) {
                $this->fail('captain_id', 'اختر كابتنًا مفعّلًا لديه بيانات توصيل.');
            }
            $o->forceFill(['captain_id' => $captainId, 'status' => 'assigned'])->save();
            $this->event($o, $actor, 'إسناد الطلب إلى ' . $captain->name);

            return $o;
        }, 3);
    }

    public function transition(Staff|User $actor, Order $order, string $target, ?string $note = null, ?int $collected = null, array $payment = []): Order
    {
        return DB::transaction(function () use ($actor, $order, $target, $note, $collected, $payment) {
            // All benefit allocation and release serializes on the store row.
            Store::whereKey($order->store_id)->lockForUpdate()->firstOrFail();
            $o = Order::lockForUpdate()->findOrFail($order->id);
            $admin = $actor instanceof Staff && $actor->isActive() && $actor->hasAnyRole(['super_admin', 'operations']);
            $captain = $actor instanceof Staff && $actor->isActive() && $actor->hasRole('captain') && $o->captain_id === $actor->id;
            $owner = $actor instanceof User && $actor->isActive() && Store::whereKey($o->store_id)->where('owner_user_id', $actor->id)->exists();
            if ($target === 'cancelled') {
                abort_unless($admin || $owner, 403);
            } elseif ($target === 'returned') {
                abort_unless($admin, 403);
            } else {
                abort_unless($admin || $captain, 403);
            }
            $allowed = ['created' => ['cancelled'], 'assigned' => ['picked_up', 'cancelled'], 'picked_up' => ['out_for_delivery', 'failed'], 'out_for_delivery' => ['delivered', 'failed'], 'failed' => ['out_for_delivery', 'returned']];
            if (! in_array($target, $allowed[$o->status] ?? [], true)) {
                $this->fail('status', 'هذا الانتقال غير مسموح من حالة الطلب الحالية.');
            }
            if (in_array($target, ['failed', 'returned', 'cancelled'], true) && mb_strlen(trim($note ?? '')) < 3) {
                $this->fail('note', 'اكتب سببًا واضحًا أو تأكيد إعادة الطرد للمتجر.');
            }
            if ($target === 'delivered' && $collected !== (int) $o->collect_minor) {
                $this->fail('collected_amount', 'المبلغ المحصّل يجب أن يساوي ' . Money::show($o->collect_minor) . '.');
            }
            $o->status = $target;
            if ($target === 'delivered') {
                $o->delivered_at = now();
            }
            if (in_array($target, ['cancelled', 'returned'], true)) {
                $this->release($o);
            }
            if ($target === 'delivered') {
                $o->benefit_reserved = false;
            }
            $o->save();
            $this->event($o, $actor, $note);
            if (in_array($target, ['delivered', 'returned'], true)) {
                $isDelivery = $target === 'delivered';
                $receipt = $isDelivery ? app(Payments::class)->record($actor, $o, $payment) : null;
                $direct = $receipt?->recipient === 'merchant' ? (int) $o->collect_minor : 0;
                $cash = $isDelivery ? (int) $o->collect_minor - $direct : 0;
                $fee = $isDelivery
                    ? (int) $o->fee_minor
                    : (int) $o->fee_minor + (int) $o->return_fee_minor;
                $entry = new FinancialEntry;
                $entry->forceFill(['order_id' => $o->id, 'store_id' => $o->store_id, 'captain_id' => $o->captain_id, 'outcome' => $target, 'collected_minor' => $cash, 'merchant_received_minor' => $direct, 'fee_minor' => $fee, 'captain_pay_minor' => $isDelivery ? $o->captain_pay_minor : $o->captain_return_pay_minor, 'merchant_net_minor' => $cash - $fee])->save();
            }

            return $o;
        }, 3);
    }

    private function release(Order $o): void
    {
        if ($o->benefit_reserved && $o->subscription_id) {
            Subscription::whereKey($o->subscription_id)->increment('remaining_deliveries');
        }
        if ($o->benefit_reserved && $o->delivery_credit_id) {
            DeliveryCredit::whereKey($o->delivery_credit_id)->increment('remaining');
        }
        $o->benefit_reserved = false;
        CouponRedemption::where('order_id', $o->id)->delete();
    }

    private function event(Order $o, Staff|User $actor, ?string $note): void
    {
        $e = new OrderEvent;
        $e->forceFill(['order_id' => $o->id, 'status' => $o->status, 'note' => $note, 'staff_id' => $actor instanceof Staff ? $actor->id : null, 'user_id' => $actor instanceof User ? $actor->id : null])->save();
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
