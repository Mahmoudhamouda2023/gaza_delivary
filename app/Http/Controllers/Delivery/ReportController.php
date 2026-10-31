<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\FinancialEntry;
use App\Models\Order;
use GazaDelivery\Foundation\Models\Staff;
use GazaDelivery\Foundation\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

final class ReportController extends Controller
{
    public function __invoke(Request $r)
    {
        $d = $r->validate(['from' => ['nullable', 'date_format:Y-m-d'], 'to' => array_merge(['nullable', 'date_format:Y-m-d'], $r->filled('from') ? ['after_or_equal:from'] : [])]);
        $from = $d['from'] ?? now('Asia/Hebron')->startOfMonth()->toDateString();
        $to = $d['to'] ?? now('Asia/Hebron')->toDateString();
        $start = Carbon::parse($from, 'Asia/Hebron')->startOfDay()->utc();
        $end = Carbon::parse($to, 'Asia/Hebron')->endOfDay()->utc();
        $q = FinancialEntry::whereBetween('created_at', [$start, $end]);
        $byStore = (clone $q)->selectRaw('store_id, COUNT(*) AS total, SUM(collected_minor + merchant_received_minor) AS cash, SUM(fee_minor) AS fees, SUM(merchant_net_minor) AS net')->groupBy('store_id')->get();
        $byCaptain = (clone $q)->selectRaw('captain_id, COUNT(*) AS total, SUM(collected_minor + merchant_received_minor) AS cash, SUM(captain_pay_minor) AS pay')->groupBy('captain_id')->get();

        return view('delivery.reports', ['from' => $from, 'to' => $to, 'fees' => (clone $q)->sum('fee_minor'), 'pay' => (clone $q)->sum('captain_pay_minor'), 'expenses' => Expense::whereDate('spent_on', '>=', $from)->whereDate('spent_on', '<=', $to)->sum('amount_minor'), 'byStore' => $byStore, 'byCaptain' => $byCaptain, 'storeNames' => Store::whereIn('id', $byStore->pluck('store_id'))->pluck('name', 'id'), 'captainNames' => Staff::whereIn('id', $byCaptain->pluck('captain_id'))->pluck('name', 'id'), 'statuses' => Order::whereBetween('created_at', [$start, $end])->selectRaw('status,COUNT(*) AS total')->groupBy('status')->pluck('total', 'status')]);
    }
}
