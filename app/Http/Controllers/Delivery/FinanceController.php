<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\CaptainPayout;
use App\Models\Expense;
use App\Models\FinancialEntry;
use App\Models\Remittance;
use App\Models\Settlement;
use App\Models\Subscription;
use App\Services\Delivery\Audit;
use App\Services\Delivery\Finance;
use App\Services\Delivery\Money;
use GazaDelivery\Foundation\Models\Staff;
use GazaDelivery\Foundation\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class FinanceController extends Controller
{
    public function index()
    {
        $ready = FinancialEntry::whereNull('settlement_id')->cleared()->selectRaw('store_id, SUM(merchant_net_minor) as amount, COUNT(*) as entries')->groupBy('store_id')->get();
        $storeNames = Store::whereIn('id', $ready->pluck('store_id'))->pluck('name', 'id');
        $pay = FinancialEntry::whereNull('captain_payout_id')->cleared()->selectRaw('captain_id, SUM(captain_pay_minor) as amount, COUNT(*) as entries')->groupBy('captain_id')->get();

        return view('delivery.finance', [
            'settlementHashes' => FinancialEntry::whereNull('settlement_id')->cleared()->orderBy('id')->get(['id', 'store_id'])->groupBy('store_id')->map(fn ($rows) => Finance::fingerprint($rows->modelKeys())),
            'payoutHashes' => FinancialEntry::whereNull('captain_payout_id')->cleared()->orderBy('id')->get(['id', 'captain_id'])->groupBy('captain_id')->map(fn ($rows) => Finance::fingerprint($rows->modelKeys())),
            'ready' => $ready, 'storeNames' => $storeNames, 'pay' => $pay, 'captainNames' => Staff::whereIn('id', $pay->pluck('captain_id'))->pluck('name', 'id'),
            'remittances' => Remittance::with('captain')->latest('id')->paginate(15),
            'fees' => FinancialEntry::sum('fee_minor'), 'commissions' => FinancialEntry::sum('captain_pay_minor'), 'expenses' => Expense::sum('amount_minor'),
            'cash' => Remittance::where('status', 'approved')->sum('amount_minor'), 'packageRevenue' => Subscription::where('status', 'active')->sum('price_minor'),
            'settlements' => Settlement::with('store')->latest('id')->limit(20)->get(), 'payouts' => CaptainPayout::with('captain')->latest('id')->limit(20)->get(),
            'expenseRows' => Expense::latest('id')->limit(20)->get(),
        ]);
    }

    public function remittance(Request $r, Remittance $remittance, Finance $finance)
    {
        $d = $r->validate(['decision' => ['required', Rule::in(['approved', 'rejected'])], 'reference' => ['required', 'string', 'min:3', 'max:160'], 'received_amount' => ['nullable', 'regex:/^\d{1,7}(\.\d{1,2})?$/']]);
        $finance->reviewRemittance($r->user('staff'), $remittance, $d['decision'] === 'approved', $d['reference'], isset($d['received_amount']) ? Money::minor($d['received_amount'], 'received_amount') : null);

        return back()->with('status', 'تم تسجيل قرار التوريد.');
    }

    public function settle(Request $r, Store $store, Finance $finance)
    {
        $d = $r->validate(['reference' => ['required', 'string', 'min:3', 'max:160'], 'entry_hash' => ['required', 'string', 'size:64'], 'expected_minor' => ['required', 'integer', 'between:-99999999999,99999999999']]);
        $s = $finance->settle($r->user('staff'), $store, $d['reference'], (int) $d['expected_minor'], $d['entry_hash']);

        return redirect()->route('admin.finance.receipt', ['kind' => 'settlement', 'id' => $s->id])->with('status', 'تم تسجيل دفع التسوية.');
    }

    public function payout(Request $r, Staff $captain, Finance $finance)
    {
        $d = $r->validate(['reference' => ['required', 'string', 'min:3', 'max:160'], 'entry_hash' => ['required', 'string', 'size:64'], 'expected_minor' => ['required', 'integer', 'between:0,99999999999']]);
        $p = $finance->payCaptain($r->user('staff'), $captain, $d['reference'], (int) $d['expected_minor'], $d['entry_hash']);

        return redirect()->route('admin.finance.receipt', ['kind' => 'captain', 'id' => $p->id])->with('status', 'تم تسجيل صرف المستحقات.');
    }

    public function expense(Request $r)
    {
        $d = $r->validate(['spent_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'], 'category' => ['required', 'string', 'max:100'], 'description' => ['required', 'string', 'max:500'], 'amount' => ['required', 'regex:/^\d{1,7}(\.\d{1,2})?$/'], 'reference' => ['required', 'string', 'min:3', 'max:160']]);
        $amount = Money::minor($d['amount']);
        if ($amount < 1) {
            throw ValidationException::withMessages(['amount' => 'المبلغ يجب أن يكون أكبر من صفر.']);
        } unset($d['amount']);
        DB::transaction(function () use ($r, $d, $amount) {
            $e = new Expense;
            $e->forceFill($d + ['amount_minor' => $amount, 'recorded_by' => $r->user('staff')->id])->save();
            Audit::log($r->user('staff'), 'expense.recorded', (string) $e->id, ['amount_minor' => $amount]);
        });

        return back()->with('status', 'تم تسجيل المصروف.');
    }

    public function captain(Request $r)
    {
        $id = $r->user('staff')->id;

        return view('delivery.captain-finance', ['unremitted' => FinancialEntry::where('captain_id', $id)->paymentVerified()->whereNull('remittance_id')->sum('collected_minor'), 'remittances' => Remittance::where('captain_id', $id)->latest('id')->paginate(15), 'earned' => FinancialEntry::where('captain_id', $id)->sum('captain_pay_minor'), 'paid' => CaptainPayout::where('captain_id', $id)->sum('amount_minor'), 'entries' => FinancialEntry::where('captain_id', $id)->with('order')->latest('id')->limit(30)->get()]);
    }

    public function requestRemittance(Request $r, Finance $finance)
    {
        $finance->requestRemittance($r->user('staff'));

        return back()->with('status', 'تم إنشاء كشف التوريد. سلّم المبلغ كاملًا للمالية لاعتماده.');
    }

    public function merchant(Request $r)
    {
        $id = $r->user('merchant')->store->id;

        return view('delivery.merchant-finance', ['entries' => FinancialEntry::where('store_id', $id)->with('order.payment', 'remittance')->latest('id')->paginate(20), 'settlements' => Settlement::where('store_id', $id)->latest('id')->limit(30)->get(), 'ready' => FinancialEntry::where('store_id', $id)->whereNull('settlement_id')->cleared()->sum('merchant_net_minor'), 'pending' => FinancialEntry::where('store_id', $id)->whereNull('settlement_id')->sum('merchant_net_minor')]);
    }

    public function receipt(Request $r, string $kind, int $id)
    {
        $isMerchant = $r->is('merchant/*');
        if ($kind === 'settlement') {
            $q = Settlement::query();
            if ($isMerchant) {
                $q->where('store_id', $r->user('merchant')->store->id);
            } $batch = $q->findOrFail($id);
            $name = $batch->store->name;
            $column = 'settlement_id';
            $title = 'كشف تسوية المتجر';
        } elseif ($kind === 'captain' && ! $isMerchant) {
            $batch = CaptainPayout::findOrFail($id);
            $name = $batch->captain->name;
            $column = 'captain_payout_id';
            $title = 'كشف صرف مستحقات الكابتن';
        } elseif ($kind === 'remittance' && ! $isMerchant) {
            $batch = Remittance::findOrFail($id);
            $name = $batch->captain->name;
            $column = 'remittance_id';
            $title = 'كشف توريد التحصيل';
        } else {
            abort(404);
        }

        return view('delivery.receipt', ['batch' => $batch, 'name' => $name, 'title' => $title, 'kind' => $kind, 'entries' => FinancialEntry::where($column, $batch->id)->with('order')->get()]);
    }
}
