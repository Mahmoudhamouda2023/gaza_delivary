<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\Neighborhood;
use App\Models\PickupAddress;
use App\Models\Zone;
use GazaDelivery\Foundation\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class AddressController extends Controller
{
    public function index(Request $r)
    {
        return view('delivery.addresses', ['addresses' => PickupAddress::where('store_id', $r->user('merchant')->store->id)->with('zone')->latest('id')->paginate(15), 'neighborhoods' => Neighborhood::where('active', true)->with('zone')->orderBy('name')->get(), 'zones' => Zone::where('active', true)->orderBy('name')->get()]);
    }

    public function store(Request $r)
    {
        $d = $r->validate(['label' => ['required', 'string', 'max:100'], 'zone_id' => ['required', Rule::exists('zones', 'id')->where('active', true)], 'address' => ['required', 'string', 'min:5', 'max:500'], 'contact_phone' => ['required', 'string', 'max:40'], 'neighborhood_id' => ['nullable', Rule::exists('neighborhoods', 'id')->where('zone_id', $r->input('zone_id'))->where('active', true)]]);
        $d['contact_phone'] = PhoneNumber::normalize($d['contact_phone']);
        $a = new PickupAddress;
        $a->forceFill($d + ['store_id' => $r->user('merchant')->store->id, 'active' => true])->save();

        return back()->with('status', 'تمت إضافة عنوان الاستلام.');
    }

    public function toggle(Request $r, int $address)
    {
        $a = PickupAddress::where('store_id', $r->user('merchant')->store->id)->findOrFail($address);
        $a->active = ! $a->active;
        $a->save();

        return back()->with('status', 'تم تحديث إتاحة العنوان. يمكنك إضافة عنوان جديد عند تغيير التفاصيل.');
    }
}
