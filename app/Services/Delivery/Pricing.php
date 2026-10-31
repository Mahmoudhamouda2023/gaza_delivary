<?php

namespace App\Services\Delivery;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\DeliveryCredit;
use App\Models\Neighborhood;
use App\Models\PickupAddress;
use App\Models\StoreAgreement;
use App\Models\Tariff;
use App\Models\Zone;
use GazaDelivery\Foundation\Models\Store;
use Illuminate\Validation\ValidationException;

final class Pricing
{
    public function quote(Store $store, array $data): array
    {
        $pickup = PickupAddress::query()->where('store_id', $store->id)->where('active', true)->find($data['pickup_address_id']);
        if (! $pickup || ! Zone::whereKey($pickup->zone_id)->where('active', true)->exists() || ! Zone::whereKey($data['to_zone_id'])->where('active', true)->exists()) {
            $this->fail('pickup_address_id', 'عنوان الاستلام أو منطقة التوصيل غير متاح.');
        }
        $tariff = Tariff::where('from_zone_id', $pickup->zone_id)->where('to_zone_id', $data['to_zone_id'])->where('active', true)->lockForUpdate()->first();
        if (! $tariff) {
            $this->fail('to_zone_id', 'لا توجد تسعيرة مفعّلة لهذا المسار. تواصل مع الإدارة.');
        }
        $neighborhood = null;
        if (! empty($data['to_neighborhood_id'])) {
            $neighborhood = Neighborhood::whereKey($data['to_neighborhood_id'])->where('zone_id', $data['to_zone_id'])->where('active', true)->first();
            if (! $neighborhood) {
                $this->fail('to_neighborhood_id', 'اختر حيًا مفعّلًا ضمن منطقة المستلم.');
            }
        } elseif (Neighborhood::where('zone_id', $data['to_zone_id'])->where('active', true)->exists()) {
            $this->fail('to_neighborhood_id', 'اختر حي المستلم ضمن المنطقة المحددة.');
        }
        if ($pickup->neighborhood_id && ! Neighborhood::whereKey($pickup->neighborhood_id)->where('zone_id', $pickup->zone_id)->where('active', true)->exists()) {
            $this->fail('pickup_address_id', 'حي الاستلام موقوف. حدّث عنوان الاستلام.');
        }
        $base = (int) $tariff->fee_minor;
        $discount = 0;
        $source = null;
        $subscriptionId = null;
        $creditId = null;
        $couponId = null;
        $today = now('Asia/Hebron')->toDateString();
        $agreement = StoreAgreement::where('store_id', $store->id)->where('active', true)->whereDate('starts_on', '<=', $today)->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $today))->first();
        if ($agreement) {
            $discount = Money::percent($base, (int) $agreement->discount_bps);
            $source = 'اتفاق المتجر';
        }
        $gift = DeliveryCredit::where('store_id', $store->id)->where('remaining', '>', 0)->whereDate('expires_on', '>=', $today)->orderBy('expires_on')->lockForUpdate()->first();
        // Gifts are reserved only when they add value.
        if ($base > $discount && $gift) {
            $discount = $base;
            $source = 'هدية توصيل';
            $creditId = $gift->id;

        }
        if (! empty($data['coupon_code'])) {
            $coupon = Coupon::where('code', strtoupper(trim($data['coupon_code'])))->lockForUpdate()->first();
            if (! $coupon || ! $coupon->active || $coupon->starts_on->toDateString() > $today || $coupon->ends_on->toDateString() < $today || ($coupon->store_id && $coupon->store_id !== $store->id) || $base < $coupon->min_fee_minor) {
                $this->fail('coupon_code', 'الكوبون غير صالح لهذا الطلب.');
            }
            if (CouponRedemption::where('coupon_id', $coupon->id)->count() >= $coupon->max_uses || CouponRedemption::where('coupon_id', $coupon->id)->where('store_id', $store->id)->count() >= $coupon->per_store_limit) {
                $this->fail('coupon_code', 'تم استنفاد استخدامات الكوبون.');
            }
            $d = $coupon->type === 'percent' ? Money::percent($base, (int) $coupon->value) : (int) $coupon->value;
            if ($coupon->max_discount_minor !== null) {
                $d = min($d, (int) $coupon->max_discount_minor);
            }
            $d = min($base, $d);
            if ($d <= $discount) {
                $this->fail('coupon_code', 'خصمك الحالي أفضل أو مساوٍ للكوبون. احذف الكود للاستفادة من ميزتك الحالية.');
            }
            $discount = $d;
            $source = 'كوبون '.$coupon->code;
            $couponId = $coupon->id;
            $creditId = null;
            $subscriptionId = null;
        }
        $fee = max(0, $base - $discount);
        $goods = Money::minor($data['goods_amount'], 'goods_amount');
        $captainPay = $tariff->captain_pay_mode === 'percent' ? Money::percent($fee, (int) $tariff->captain_share_bps) : (int) $tariff->captain_pay_minor;

        return ['pickup_address_id' => $pickup->id, 'pickup_snapshot' => $pickup->label.' — '.$pickup->zone->name.($pickup->neighborhood ? ' · '.$pickup->neighborhood->name : '').' — '.$pickup->address, 'pickup_phone' => $pickup->contact_phone, 'to_neighborhood_id' => $neighborhood?->id, 'captain_pay_mode' => $tariff->captain_pay_mode, 'captain_share_bps' => (int) $tariff->captain_share_bps, 'destination_snapshot' => Zone::findOrFail($data['to_zone_id'])->name.($neighborhood ? ' · '.$neighborhood->name : ''), 'goods_minor' => $goods, 'base_fee_minor' => $base, 'discount_minor' => $discount, 'fee_minor' => $fee, 'captain_pay_minor' => $captainPay, 'return_fee_minor' => (int) $tariff->return_fee_minor, 'captain_return_pay_minor' => (int) $tariff->captain_return_pay_minor, 'collect_minor' => $goods + ($data['fee_payer'] === 'recipient' ? $fee : 0), 'discount_source' => $source, 'coupon_id' => $couponId, 'subscription_id' => $subscriptionId, 'delivery_credit_id' => $creditId, 'benefit_reserved' => $creditId !== null || $subscriptionId !== null];
    }

    public static function fingerprint(array $quote): string
    {
        return hash_hmac('sha256', json_encode($quote, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), (string) config('app.key'));
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
