<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\DeliveryCredit;
use App\Models\StoreAgreement;
use App\Services\Delivery\Audit;
use GazaDelivery\Foundation\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class BenefitController extends Controller
{
    public function index(Request $r)
    {
        $id = $r->user('merchant')->store->id;

        return view('delivery.benefits', ['credits' => DeliveryCredit::where('store_id', $id)->where('remaining', '>', 0)->whereDate('expires_on', '>=', now('Asia/Hebron')->toDateString())->get(), 'agreement' => StoreAgreement::where('store_id', $id)->where('active', true)->whereDate('starts_on', '<=', now('Asia/Hebron')->toDateString())->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', now('Asia/Hebron')->toDateString()))->first()]);
    }

    public function admin()
    {
        return view('delivery.subscriptions', ['stores' => Store::where('status', 'active')->orderBy('name')->get(), 'credits' => DeliveryCredit::with('store')->latest('id')->limit(20)->get()]);
    }

    public function gift(Request $r)
    {
        $d = $r->validate(['store_id' => ['required', 'exists:stores,id'], 'quantity' => ['required', 'integer', 'between:1,10000'], 'expires_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.now('Asia/Hebron')->toDateString()], 'reason' => ['required', 'string', 'min:3', 'max:500']]);
        DB::transaction(function () use ($d, $r) {
            Store::whereKey($d['store_id'])->lockForUpdate()->firstOrFail();
            $c = new DeliveryCredit;
            $c->forceFill($d + ['remaining' => $d['quantity'], 'granted_by' => $r->user('staff')->id])->save();
            Audit::log($r->user('staff'), 'gift.granted', (string) $c->id, ['quantity' => $c->quantity]);
        }, 3);

        return back()->with('status', 'تم منح هدية التوصيل للمتجر.');
    }
}
