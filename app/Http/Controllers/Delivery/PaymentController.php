<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\OrderPayment;
use App\Services\Delivery\Payments;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class PaymentController extends Controller
{
    public function index()
    {
        return view('delivery.payments', ['payments' => OrderPayment::with('order.store', 'order.captain')->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")->latest('id')->paginate(20)]);
    }

    public function review(Request $r, OrderPayment $payment, Payments $payments)
    {
        $d = $r->validate(['decision' => ['required', Rule::in(['approved', 'rejected'])], 'review_note' => ['required', 'string', 'min:3', 'max:500']]);
        $payments->review($r->user('staff'), $payment, $d['decision'], $d['review_note']);

        return back()->with('status', 'تم تسجيل مراجعة الدفعة.');
    }

    public function correct(Request $r, OrderPayment $payment, Payments $payments)
    {
        $payments->correct($r->user('staff'), $payment, $r->only(['payment_method', 'payment_recipient', 'payment_account', 'payment_reference']));

        return back()->with('status', 'تم تصحيح الدفعة وإعادتها للتحقق المالي.');
    }
}
