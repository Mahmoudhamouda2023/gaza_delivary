<?php

namespace App\Services\Delivery;

use App\Models\Order;
use App\Models\OrderPayment;
use GazaDelivery\Foundation\Models\Staff;
use GazaDelivery\Foundation\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class Payments
{
    private function details(array $input): array
    {
        $input['payment_method'] ??= 'cash';
        $input['payment_recipient'] ??= 'captain';
        $d = Validator::make($input, [
            'payment_method' => ['required', Rule::in(array_keys(config('delivery.payment_methods')))],
            'payment_recipient' => ['required', Rule::in(['captain', 'merchant'])],
            'payment_account' => ['nullable', 'string', 'max:160'],
            'payment_reference' => ['nullable', 'string', 'max:160'],
        ])->validate();
        if ($d['payment_method'] === 'cash') {
            if ($d['payment_recipient'] !== 'captain') {
                throw ValidationException::withMessages(['payment_recipient' => 'التحصيل النقدي يُسجل على الكابتن. اختر محفظة أو بنكًا للتحويل المباشر للتاجر.']);
            }

            return $d;
        }
        $d['payment_account'] = mb_strtoupper(preg_replace('/\s+/u', '', trim($d['payment_account'] ?? '')));
        $d['payment_reference'] = mb_strtoupper(trim($d['payment_reference'] ?? ''));
        Validator::make($d, [
            'payment_account' => ['required', 'string', 'min:6', 'max:160'],
            'payment_reference' => ['required', 'string', 'min:3', 'max:160'],
        ])->validate();

        return $d;
    }

    public function record(Staff|User $actor, Order $order, array $input): ?OrderPayment
    {
        $d = $this->details($input);
        if ($d['payment_method'] === 'cash') {
            return null;
        }
        abort_unless($actor instanceof Staff, 403);
        try {
            $p = new OrderPayment;
            $p->forceFill([
                'order_id' => $order->id, 'method' => $d['payment_method'],
                'recipient' => $d['payment_recipient'], 'amount_minor' => $order->collect_minor,
                'account_label' => $d['payment_account'], 'reference' => $d['payment_reference'],
                'status' => 'pending', 'reported_by' => $actor->id,
            ])->save();
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['payment_reference' => 'مرجع التحويل مسجل مسبقًا لهذا الحساب والوسيلة. لا تستخدم التحويل نفسه لأكثر من طلب.']);
        }
        Audit::log($actor, 'payment.reported', (string) $p->id, ['method' => $p->method, 'recipient' => $p->recipient, 'amount_minor' => $p->amount_minor]);

        return $p;
    }

    public function review(Staff $actor, OrderPayment $payment, string $decision, string $note): void
    {
        abort_unless($actor->isActive() && $actor->hasAnyRole(['super_admin', 'finance']), 403);
        Validator::make(['decision' => $decision, 'note' => $note], ['decision' => ['required', Rule::in(['approved', 'rejected'])], 'note' => ['required', 'string', 'min:3', 'max:500']])->validate();
        DB::transaction(function () use ($actor, $payment, $decision, $note) {
            Order::whereKey($payment->order_id)->lockForUpdate()->firstOrFail();
            $p = OrderPayment::lockForUpdate()->findOrFail($payment->id);
            if ($p->status !== 'pending') {
                throw ValidationException::withMessages(['decision' => 'سبق اتخاذ قرار في هذه الدفعة.']);
            }
            $p->forceFill(['status' => $decision, 'reviewed_by' => $actor->id, 'reviewed_at' => now(), 'review_note' => $note])->save();
            Audit::log($actor, 'payment.'.$decision, (string) $p->id, ['note' => $note]);
        }, 3);
    }

    public function correct(Staff $actor, OrderPayment $payment, array $input): void
    {
        $d = $this->details($input);
        if ($d['payment_method'] === 'cash') {
            throw ValidationException::withMessages(['payment_method' => 'تصحيح المرجع للتحويلات الإلكترونية فقط. راجع المالية لتغيير طريقة التحصيل بعد التسليم.']);
        }
        try {
            DB::transaction(function () use ($actor, $payment, $d) {
                $order = Order::whereKey($payment->order_id)->lockForUpdate()->firstOrFail();
                abort_unless($actor->isActive() && $actor->hasRole('captain') && $order->captain_id === $actor->id, 403);
                $p = OrderPayment::lockForUpdate()->findOrFail($payment->id);
                if ($p->status !== 'rejected') {
                    throw ValidationException::withMessages(['payment_reference' => 'التصحيح متاح بعد رفض الدفعة فقط.']);
                }
                $e = $order->entry()->lockForUpdate()->firstOrFail();
                if ($e->remittance_id || $e->settlement_id || $e->captain_payout_id) {
                    abort(409);
                }
                $old = $p->only(['method', 'recipient', 'account_label', 'reference']);
                $p->forceFill(['method' => $d['payment_method'], 'recipient' => $d['payment_recipient'], 'account_label' => $d['payment_account'], 'reference' => $d['payment_reference'], 'status' => 'pending', 'reported_by' => $actor->id, 'reviewed_by' => null, 'reviewed_at' => null, 'review_note' => null])->save();
                $direct = $p->recipient === 'merchant' ? (int) $p->amount_minor : 0;
                $captain = (int) $p->amount_minor - $direct;
                $e->forceFill(['collected_minor' => $captain, 'merchant_received_minor' => $direct, 'merchant_net_minor' => $captain - $e->fee_minor])->save();
                Audit::log($actor, 'payment.corrected', (string) $p->id, ['previous' => $old]);
            }, 3);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['payment_reference' => 'مرجع التحويل مستخدم مسبقًا لهذا الحساب.']);
        }
    }
}
