<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\DeliveryAudit;
use App\Models\Neighborhood;
use App\Models\Order;
use App\Models\PickupAddress;
use App\Models\StoreAgreement;
use App\Models\Tariff;
use App\Models\Zone;
use App\Services\Delivery\Audit;
use App\Services\Delivery\Money;
use GazaDelivery\Foundation\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class CatalogController extends Controller
{
    private function definition(string $kind): array
    {
        $zones = fn () => Zone::orderBy('name')->pluck('name', 'id')->all();
        $stores = fn () => Store::orderBy('name')->pluck('name', 'id')->all();
        $f = fn ($name, $label, $type = 'text', $options = []) => compact('name', 'label', 'type', 'options');
        $active = $f('active', 'الحالة', 'select', ['1' => 'مفعّل', '0' => 'موقوف']);

        return match ($kind) {
            'zones' => ['المناطق', Zone::class, [$f('name', 'اسم المنطقة'), $active]],
            'neighborhoods' => ['الأحياء', Neighborhood::class, [$f('zone_id', 'المنطقة', 'select', $zones()), $f('name', 'اسم الحي'), $active]],
            'tariffs' => ['أسعار المسارات', Tariff::class, [$f('from_zone_id', 'من منطقة', 'select', $zones()), $f('to_zone_id', 'إلى منطقة', 'select', $zones()), $f('fee_minor', 'أجرة التوصيل ₪', 'money'), $f('captain_pay_minor', 'أجرة الكابتن الثابتة ₪', 'money'), $f('captain_pay_mode', 'طريقة أجرة الكابتن', 'select-optional', ['fixed' => 'مبلغ ثابت', 'percent' => 'نسبة من الأجرة النهائية']), $f('captain_share_bps', 'حصة الكابتن % (عند اختيار نسبة)', 'percent-optional'), $f('return_fee_minor', 'أجرة إعادة الطرد على المتجر ₪', 'money'), $f('captain_return_pay_minor', 'مستحق الكابتن للإعادة ₪', 'money'), $active]],
            'agreements' => ['اتفاقيات المتاجر', StoreAgreement::class, [$f('store_id', 'المتجر', 'select', $stores()), $f('discount_bps', 'خصم المتجر %', 'percent'), $f('starts_on', 'بداية الاتفاق', 'date'), $f('ends_on', 'نهاية الاتفاق (اختياري)', 'date-optional'), $f('notes', 'ملاحظات الاتفاق (اختياري)', 'optional'), $active]],
            'coupons' => ['الكوبونات', Coupon::class, [$f('code', 'كود الكوبون'), $f('store_id', 'متجر محدد (اختياري)', 'select-optional', $stores()), $f('type', 'نوع الخصم', 'select', ['percent' => 'نسبة مئوية', 'fixed' => 'مبلغ بالشيكل']), $f('value', 'قيمة الخصم (% أو ₪)', 'money'), $f('max_discount_minor', 'الحد الأعلى للخصم ₪ (اختياري)', 'money-optional'), $f('min_fee_minor', 'الحد الأدنى للأجرة الأساسية ₪', 'money'), $f('max_uses', 'إجمالي الاستخدامات', 'number'), $f('per_store_limit', 'الحد لكل متجر', 'number'), $f('starts_on', 'بداية الصلاحية', 'date'), $f('ends_on', 'نهاية الصلاحية', 'date'), $active]],
            default => abort(404),
        };
    }

    public function index(string $kind)
    {
        return $this->page($kind);
    }

    public function edit(string $kind, int $id)
    {
        return $this->page($kind, $id);
    }

    private function page(string $kind, ?int $id = null)
    {
        [$title,$model,$fields] = $this->definition($kind);

        return view('delivery.catalog', ['title' => $title, 'kind' => $kind, 'fields' => $fields, 'rows' => $model::latest('id')->paginate(15), 'editing' => $id ? $model::findOrFail($id) : null]);
    }

    public function store(Request $r, string $kind)
    {
        return $this->save($r, $kind);
    }

    public function update(Request $r, string $kind, int $id)
    {
        return $this->save($r, $kind, $id);
    }

    private function save(Request $r, string $kind, ?int $id = null)
    {
        [$title,$model,$fields] = $this->definition($kind);
        $rules = [];
        foreach ($fields as $f) {
            $optional = str_contains($f['type'], 'optional');
            $rules[$f['name']] = [$optional ? 'nullable' : 'required'];
            if (str_starts_with($f['type'], 'select')) {
                $rules[$f['name']][] = Rule::in(array_keys($f['options']));
            } elseif (str_starts_with($f['type'], 'money') || str_starts_with($f['type'], 'percent')) {
                $rules[$f['name']][] = 'regex:/^\d{1,7}(\.\d{1,2})?$/';
            } elseif (str_starts_with($f['type'], 'date')) {
                $rules[$f['name']][] = 'date_format:Y-m-d';
            } elseif (str_starts_with($f['type'], 'number')) {
                $rules[$f['name']][] = 'integer';
                $rules[$f['name']][] = $f['type'] === 'number-zero' ? 'between:0,10000' : 'between:1,10000';
            } else {
                $rules[$f['name']][] = 'string';
                $rules[$f['name']][] = 'max:'.(in_array($f['name'], ['notes', 'benefits']) ? 500 : 120);
            }
        }
        if ($kind === 'zones') {
            $rules['name'][] = Rule::unique('zones', 'name')->ignore($id);
        }
        if ($kind === 'neighborhoods') {
            $rules['name'][] = Rule::unique('neighborhoods', 'name')->where('zone_id', $r->input('zone_id'))->ignore($id);
        }
        if ($kind === 'tariffs') {
            $rules['to_zone_id'][] = Rule::unique('tariffs', 'to_zone_id')->where('from_zone_id', $r->input('from_zone_id'))->ignore($id);
        }
        if ($kind === 'agreements') {
            $rules['store_id'][] = Rule::unique('store_agreements', 'store_id')->ignore($id);
        }
        if (in_array($kind, ['agreements', 'coupons'])) {
            $rules['ends_on'][] = 'after_or_equal:starts_on';
        }
        if ($kind === 'coupons') {
            $r->merge(['code' => strtoupper(trim((string) $r->input('code')))]);
            $rules['code'] = ['required', 'regex:/^[A-Z0-9_-]{3,40}$/', Rule::unique('coupons', 'code')->ignore($id)];
        }
        $d = $r->validate($rules);
        foreach ($fields as $f) {
            if (isset($d[$f['name']]) && (str_starts_with($f['type'], 'money') || str_starts_with($f['type'], 'percent'))) {
                $d[$f['name']] = Money::minor($d[$f['name']], $f['name']);
                if ((str_starts_with($f['type'], 'percent') || ($kind === 'coupons' && $f['name'] === 'value' && $d['type'] === 'percent')) && $d[$f['name']] > 10000) {
                    throw ValidationException::withMessages([$f['name'] => 'النسبة لا تزيد عن 100%.']);
                }
            }
        }
        if ($kind === 'tariffs') {
            $d['captain_pay_mode'] = $d['captain_pay_mode'] ?? 'fixed';
            $d['captain_share_bps'] = $d['captain_share_bps'] ?? 0;
        }
        if ($kind === 'coupons' && $d['value'] < 1) {
            throw ValidationException::withMessages(['value' => 'يجب أن يكون الخصم أكبر من صفر.']);
        }
        if ($kind === 'packages' && $d['duration_days'] > 366) {
            throw ValidationException::withMessages(['duration_days' => 'المدة القصوى 366 يومًا.']);
        }
        DB::transaction(function () use ($model, $id, $d, $r, $kind) {
            $row = $id ? $model::lockForUpdate()->findOrFail($id) : new $model;
            if ($kind === 'neighborhoods' && $id && (int) $row->zone_id !== (int) $d['zone_id'] && (Order::where('to_neighborhood_id', $id)->exists() || PickupAddress::where('neighborhood_id', $id)->exists())) {
                throw ValidationException::withMessages(['zone_id' => 'هذا الحي مرتبط بعناوين أو طلبات. أوقفه وأضف حيًا جديدًا في المنطقة الأخرى.']);
            }
            $row->forceFill($d)->save();
            Audit::log($r->user('staff'), $kind.'.saved', (string) $row->id, $d);
        }, 3);

        return redirect()->route('admin.catalog.index', $kind)->with('status', 'تم الحفظ. الأسعار والمزايا الجديدة تُطبّق على الطلبات التالية فقط.');
    }

    public function audit()
    {
        return view('delivery.audit', ['rows' => DeliveryAudit::latest('id')->paginate(30)]);
    }
}
