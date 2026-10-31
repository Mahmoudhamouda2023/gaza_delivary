<?php

namespace App\Services\Delivery;

use App\Models\CaptainPayout;
use App\Models\FinancialEntry;
use App\Models\Remittance;
use App\Models\Settlement;
use GazaDelivery\Foundation\Models\Staff;
use GazaDelivery\Foundation\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class Finance
{
    public function requestRemittance(Staff $captain): Remittance
    {
        abort_unless($captain->isActive() && $captain->hasRole('captain'), 403);

        return DB::transaction(function () use ($captain) {
            Staff::whereKey($captain->id)->lockForUpdate()->firstOrFail();
            $entries = FinancialEntry::where('captain_id', $captain->id)->paymentVerified()->whereNull('remittance_id')->where('collected_minor', '>', 0)->orderBy('id')->lockForUpdate()->get();
            if ($entries->isEmpty()) {
                $this->fail('amount', 'لا توجد مبالغ جديدة جاهزة للتوريد.');
            }
            $r = new Remittance;
            $r->forceFill(['captain_id' => $captain->id, 'amount_minor' => $entries->sum('collected_minor'), 'status' => 'pending'])->save();
            FinancialEntry::whereIn('id', $entries->modelKeys())->update(['remittance_id' => $r->id]);
            Audit::log($captain, 'remittance.requested', (string) $r->id);

            return $r;
        }, 3);
    }

    public function reviewRemittance(Staff $actor, Remittance $remittance, bool $approve, string $reference, ?int $received): void
    {
        $this->authorize($actor);
        DB::transaction(function () use ($actor, $remittance, $approve, $reference, $received) {
            $r = Remittance::lockForUpdate()->findOrFail($remittance->id);
            if ($r->status !== 'pending') {
                $this->fail('status', 'سبق اتخاذ قرار في هذا التوريد.');
            }
            if ($approve && $received !== (int) $r->amount_minor) {
                $this->fail('received_amount', 'المبلغ المستلم يجب أن يطابق كامل التوريد. لا تخصم أجرة الكابتن منه.');
            }
            $r->forceFill(['status' => $approve ? 'approved' : 'rejected', 'reference' => $reference, 'reviewed_by' => $actor->id, 'reviewed_at' => now()])->save();
            if (! $approve) {
                FinancialEntry::where('remittance_id', $r->id)->update(['remittance_id' => null]);
            }
            Audit::log($actor, 'remittance.'.$r->status, (string) $r->id, ['amount_minor' => $r->amount_minor]);
        }, 3);
    }

    public function settle(Staff $actor, Store $store, string $reference, int $expected, ?string $entryHash = null): Settlement
    {
        $this->authorize($actor);

        return DB::transaction(function () use ($actor, $store, $reference, $expected, $entryHash) {
            Store::whereKey($store->id)->lockForUpdate()->firstOrFail();
            $entries = FinancialEntry::where('store_id', $store->id)->whereNull('settlement_id')->cleared()->orderBy('id')->lockForUpdate()->get();
            if ($entries->isEmpty()) {
                $this->fail('amount', 'لا توجد طلبات قابلة للتسوية بعد التوريد.');
            }
            if ($entryHash !== null && ! hash_equals(self::fingerprint($entries->modelKeys()), $entryHash)) {
                $this->fail('amount', 'تغيّرت الطلبات في الكشف. حدّث الصفحة وراجعها قبل تسجيل الدفع.');
            }
            $amount = (int) $entries->sum('merchant_net_minor');
            if ($amount !== $expected) {
                $this->fail('amount', 'تغيّر رصيد التسوية. حدّث الصفحة وراجع المبلغ.');
            }
            $s = new Settlement;
            $s->forceFill(['store_id' => $store->id, 'amount_minor' => $amount, 'reference' => $reference, 'paid_by' => $actor->id])->save();
            FinancialEntry::whereIn('id', $entries->modelKeys())->update(['settlement_id' => $s->id]);
            Audit::log($actor, 'settlement.paid', (string) $s->id, ['amount_minor' => $amount]);

            return $s;
        }, 3);
    }

    public function payCaptain(Staff $actor, Staff $captain, string $reference, int $expected, ?string $entryHash = null): CaptainPayout
    {
        $this->authorize($actor);

        return DB::transaction(function () use ($actor, $captain, $reference, $expected, $entryHash) {
            Staff::whereKey($captain->id)->lockForUpdate()->firstOrFail();
            $entries = FinancialEntry::where('captain_id', $captain->id)->whereNull('captain_payout_id')->cleared()->orderBy('id')->lockForUpdate()->get();
            if ($entries->isEmpty()) {
                $this->fail('amount', 'لا توجد مستحقات جاهزة للصرف.');
            }
            if ($entryHash !== null && ! hash_equals(self::fingerprint($entries->modelKeys()), $entryHash)) {
                $this->fail('amount', 'تغيّرت الطلبات في الكشف. حدّث الصفحة وراجعها قبل تسجيل الدفع.');
            }
            $amount = (int) $entries->sum('captain_pay_minor');
            if ($amount !== $expected) {
                $this->fail('amount', 'تغيّر المبلغ. حدّث الصفحة وراجع المستحقات.');
            }
            $p = new CaptainPayout;
            $p->forceFill(['captain_id' => $captain->id, 'amount_minor' => $amount, 'reference' => $reference, 'paid_by' => $actor->id])->save();
            FinancialEntry::whereIn('id', $entries->modelKeys())->update(['captain_payout_id' => $p->id]);
            Audit::log($actor, 'captain.paid', (string) $p->id, ['amount_minor' => $amount]);

            return $p;
        }, 3);
    }

    public static function fingerprint(array $ids): string
    {
        sort($ids, SORT_NUMERIC);

        return hash_hmac('sha256', implode(',', $ids), (string) config('app.key'));
    }

    private function authorize(Staff $actor): void
    {
        abort_unless($actor->isActive() && $actor->hasAnyRole(['super_admin', 'finance']), 403);
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
