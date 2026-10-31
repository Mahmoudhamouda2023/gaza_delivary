<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\Neighborhood;
use App\Models\Order;
use App\Models\OrderIssue;
use App\Models\PickupAddress;
use App\Models\Zone;
use App\Services\Delivery\Money;
use App\Services\Delivery\OrderSchedule;
use App\Services\Delivery\OrderWorkflow;
use App\Services\Delivery\Pricing;
use GazaDelivery\Foundation\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class OrderController extends Controller
{
    public function index(Request $r)
    {
        $q = $this->filtered($r);
        $prefix = $this->prefix($r);

        return view('delivery.orders', ['orders' => $q->with('store', 'captain')->latest('id')->paginate(20)->withQueryString(), 'prefix' => $prefix]);
    }

    public function create(Request $r)
    {
        $store = $r->user('merchant')->store;

        return view('delivery.order-create', ['addresses' => PickupAddress::where('store_id', $store->id)->where('active', true)->with('zone')->get(), 'zones' => Zone::where('active', true)->orderBy('name')->get(), 'neighborhoods' => Neighborhood::where('active', true)->with('zone')->orderBy('name')->get(), 'token' => (string) Str::uuid()]);
    }

    public function preview(Request $r, Pricing $pricing)
    {
        $data = $this->validateOrder($r);
        OrderSchedule::attributes($data);
        $quote = DB::transaction(fn () => $pricing->quote($r->user('merchant')->store, $data), 3);

        return view('delivery.order-review', compact('data', 'quote'));
    }

    public function store(Request $r, OrderWorkflow $workflow)
    {
        $data = $this->validateOrder($r);
        $confirmation = $r->validate(['expected_fee' => ['required', 'integer', 'min:0', 'max:999999999'], 'quote_hash' => ['required', 'string', 'size:64']]);
        $data = array_merge($data, $confirmation);
        $order = $workflow->create($r->user('merchant'), $data);

        return redirect()->route('merchant.orders.show', $order)->with('status', 'تم إنشاء الطلب وحجز الميزة إن وجدت.');
    }

    public function show(Request $r, string $order)
    {
        $o = $this->query($r)->where('public_id', $order)->firstOrFail()->load('store', 'captain', 'events', 'issues', 'entry', 'payment');

        return view('delivery.order-show', ['order' => $o, 'prefix' => $this->prefix($r), 'captains' => $r->is('admin/*') ? Staff::role('captain')->where('status', 'active')->with('captainProfile')->get() : collect()]);
    }

    public function assign(Request $r, string $order, OrderWorkflow $workflow)
    {
        $data = $r->validate(['captain_id' => ['required', 'integer', 'exists:staff,id']]);
        $workflow->assign($r->user('staff'), $this->query($r)->where('public_id', $order)->firstOrFail(), $data['captain_id']);

        return back()->with('status', 'تم إسناد الطلب.');
    }

    public function transition(Request $r, string $order, OrderWorkflow $workflow)
    {
        $data = $r->validate(['status' => ['required', Rule::in(array_keys(config('delivery.order_statuses')))], 'note' => ['nullable', 'string', 'max:500'], 'collected_amount' => ['nullable', 'regex:/^\d{1,7}(\.\d{1,2})?$/']]);
        $workflow->transition($r->user($this->prefix($r) === 'merchant' ? 'merchant' : 'staff'), $this->query($r)->where('public_id', $order)->firstOrFail(), $data['status'], $data['note'] ?? null, isset($data['collected_amount']) ? Money::minor($data['collected_amount'], 'collected_amount') : null, $r->only(['payment_method', 'payment_recipient', 'payment_account', 'payment_reference']));

        return back()->with('status', 'تم تحديث الطلب وتسجيل الإجراء.');
    }

    public function issue(Request $r, string $order)
    {
        $data = $r->validate(['message' => ['required', 'string', 'min:5', 'max:1000']]);
        $o = $this->query($r)->where('public_id', $order)->firstOrFail();
        $issue = new OrderIssue;
        $issue->forceFill(['order_id' => $o->id, 'user_id' => $r->user('merchant')->id, 'message' => $data['message']])->save();

        return back()->with('status', 'وصلت ملاحظتك لفريق التشغيل.');
    }

    public function resolveIssue(Request $r, OrderIssue $issue)
    {
        $data = $r->validate(['reply' => ['required', 'string', 'min:3', 'max:1000']]);
        DB::transaction(function () use ($issue, $data, $r) {
            $i = OrderIssue::lockForUpdate()->findOrFail($issue->id);
            if ($i->resolved_at) {
                abort(409, 'سبق الرد على الملاحظة.');
            } $i->forceFill(['reply' => $data['reply'], 'resolved_by' => $r->user('staff')->id, 'resolved_at' => now()])->save();
        });

        return back()->with('status', 'تم حفظ الرد.');
    }

    public function issues()
    {
        return view('delivery.issues', ['issues' => OrderIssue::with('order.store')->whereNull('resolved_at')->oldest()->paginate(20)]);
    }

    public function tracking(string $token)
    {
        $order = Order::where('tracking_token', $token)->firstOrFail()->load('events', 'store');

        return view('delivery.tracking', compact('order'));
    }

    public function export(Request $r)
    {
        $query = $this->filtered($r)->with('store');

        return response()->streamDownload(function () use ($query) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['رقم الطلب', 'المتجر', 'الحالة', 'المرجع', 'قيمة البضاعة', 'أجرة التوصيل', 'المطلوب تحصيله', 'تاريخ الإنشاء', 'موعد التوصيل']);
            foreach ($query->latest('id')->limit(5000)->get() as $o) {
                $row = [$o->public_id, $o->store->name, $o->label, $o->merchant_reference, $o->goods_minor / 100, $o->fee_minor / 100, $o->collect_minor / 100, $o->created_at->toIso8601String(), $o->delivery_time_label];
                fputcsv($f, array_map(fn ($v) => preg_match('/^[=+\-@\t\r]/', ltrim((string) $v)) ? "'".$v : $v, $row));
            } fclose($f);
        }, 'delivery-orders.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function validateOrder(Request $r): array
    {
        return $r->validate(['idempotency_key' => ['required', 'uuid'], 'pickup_address_id' => ['required', 'integer'], 'to_zone_id' => ['required', 'integer'], 'recipient_name' => ['required', 'string', 'max:120'], 'recipient_phone' => ['required', 'string', 'max:40'], 'recipient_address' => ['required', 'string', 'min:5', 'max:500'], 'description' => ['required', 'string', 'max:500'], 'merchant_reference' => ['nullable', 'string', 'max:100'], 'notes' => ['nullable', 'string', 'max:500'], 'fee_payer' => ['required', Rule::in(['merchant', 'recipient'])], 'goods_amount' => ['required', 'regex:/^\d{1,7}(\.\d{1,2})?$/'], 'coupon_code' => ['nullable', 'string', 'max:40'], 'to_neighborhood_id' => ['nullable', 'integer'], 'delivery_mode' => ['sometimes', Rule::in(['now', 'scheduled'])], 'scheduled_for' => ['nullable', 'string', 'max:30']]);
    }

    private function filtered(Request $r)
    {
        $d = $r->validate(['q' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', Rule::in(array_keys(config('delivery.order_statuses')))], 'scheduled' => ['nullable', Rule::in(['1'])], 'from' => ['nullable', 'date_format:Y-m-d'], 'to' => array_merge(['nullable', 'date_format:Y-m-d'], $r->filled('from') ? ['after_or_equal:from'] : [])]);

        return $this->query($r)->when($d['scheduled'] ?? null, fn ($q) => $q->whereNotNull('scheduled_for')->whereNotIn('status', ['delivered', 'returned', 'cancelled']))->when($d['status'] ?? null, fn ($q, $s) => $q->where('status', $s))->when($d['q'] ?? null, fn ($q, $s) => $q->where(fn ($x) => $x->where('public_id', 'like', '%'.$s.'%')->orWhere('merchant_reference', 'like', '%'.$s.'%')->orWhere('recipient_name', 'like', '%'.$s.'%')))->when($d['from'] ?? null, fn ($q, $s) => $q->whereDate('created_at', '>=', $s))->when($d['to'] ?? null, fn ($q, $s) => $q->whereDate('created_at', '<=', $s));
    }

    private function query(Request $r)
    {
        $prefix = $this->prefix($r);

        return Order::query()->when($prefix === 'merchant', fn ($q) => $q->where('store_id', $r->user('merchant')->store->id))->when($prefix === 'captain', fn ($q) => $q->where('captain_id', $r->user('staff')->id));
    }

    private function prefix(Request $r): string
    {
        return $r->is('admin/*') ? 'admin' : ($r->is('captain/*') ? 'captain' : 'merchant');
    }
}
