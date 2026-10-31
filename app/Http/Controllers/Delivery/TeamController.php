<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Delivery\Audit;
use GazaDelivery\Foundation\Models\CaptainProfile;
use GazaDelivery\Foundation\Models\Staff;
use GazaDelivery\Foundation\Models\Store;
use GazaDelivery\Foundation\Models\User;
use GazaDelivery\Foundation\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

final class TeamController extends Controller
{
    public function edit(Staff $staff)
    {
        return view('delivery.team-edit', compact('staff'));
    }

    public function update(Request $r, Staff $staff)
    {
        $r->merge(['phone' => PhoneNumber::normalize((string) $r->input('phone'))]);
        $d = $r->validate(['name' => ['required', 'string', 'max:120'], 'phone' => ['required', Rule::unique('staff', 'phone')->ignore($staff->id)], 'email' => ['nullable', 'email:rfc', 'max:254'], 'roles' => ['required', 'array', 'min:1'], 'roles.*' => ['required', 'distinct', Rule::in(array_keys(config('delivery.role_labels')))], 'status' => ['required', Rule::in(['active', 'suspended'])], 'vehicle_type' => ['nullable', Rule::in(['bicycle', 'electric_bicycle'])], 'reason' => ['required', 'string', 'min:3', 'max:500']]);
        if (in_array('captain', $d['roles']) && empty($d['vehicle_type'])) {
            throw ValidationException::withMessages(['vehicle_type' => 'حدد وسيلة توصيل الكابتن.']);
        }
        DB::transaction(function () use ($r, $staff, $d) {
            Role::where('name', 'super_admin')->where('guard_name', 'staff')->lockForUpdate()->firstOrFail();
            $actor = Staff::lockForUpdate()->findOrFail($r->user('staff')->id);
            abort_unless($actor->isActive() && $actor->hasRole('super_admin'), 403);
            $s = Staff::lockForUpdate()->findOrFail($staff->id);
            if ($s->isActive() && $s->hasRole('super_admin') && ($d['status'] !== 'active' || ! in_array('super_admin', $d['roles'])) && Staff::where('status', 'active')->role('super_admin')->count() <= 1) {
                throw ValidationException::withMessages(['roles' => 'لا يمكن إزالة صلاحية آخر مشرف عام فعّال أو إيقافه.']);
            }
            if (($d['status'] !== 'active' || ! in_array('captain', $d['roles'])) && Order::where('captain_id', $s->id)->whereIn('status', ['assigned', 'picked_up', 'out_for_delivery', 'failed'])->exists()) {
                throw ValidationException::withMessages(['status' => 'للكابتن طلبات مفتوحة. أنهِها أو أعد إسناد الطلبات التي لم تُستلم أولًا.']);
            }
            $s->forceFill(['name' => $d['name'], 'phone' => $d['phone'], 'email' => $d['email'] ?? null, 'status' => $d['status'], 'session_version' => $s->session_version + 1])->save();
            $s->syncRoles($d['roles']);
            if (in_array('captain', $d['roles'])) {
                $p = CaptainProfile::firstOrNew(['staff_id' => $s->id]);
                $p->forceFill(['staff_id' => $s->id, 'vehicle_type' => $d['vehicle_type']])->save();
            }
            Audit::log($actor, 'staff.updated', (string) $s->id, ['roles' => $d['roles'], 'status' => $d['status'], 'reason' => $d['reason']]);
        }, 3);

        return redirect()->route($r->user('staff')->id === $staff->id ? 'staff.login' : 'admin.staff.index')->with('status', 'تم التحديث وإبطال جلسات الحساب.');
    }

    public function resetStaff(Request $r, Staff $staff)
    {
        $this->reset($r, $staff);

        return back()->with('status', 'تم تعيين كلمة مرور مؤقتة وإبطال الجلسات السابقة.');
    }

    public function resetMerchant(Request $r, Store $store)
    {
        $this->reset($r, $store->owner);

        return back()->with('status', 'تم تعيين كلمة مرور مؤقتة لصاحب المتجر.');
    }

    private function reset(Request $r, Staff|User $subject): void
    {
        $d = $r->validate(['password' => ['required', 'string', 'max:256', 'confirmed', Password::min(8)->letters()->numbers()], 'password_confirmation' => ['required', 'string'], 'reason' => ['required', 'string', 'min:3', 'max:500']]);
        DB::transaction(function () use ($r, $subject, $d) {
            $s = $subject->newQuery()->lockForUpdate()->findOrFail($subject->id);
            $s->forceFill(['password' => $d['password'], 'must_change_password' => true, 'session_version' => $s->session_version + 1, 'remember_token' => null])->save();
            Audit::log($r->user('staff'), 'password.reset', get_class($s).':'.$s->id, ['reason' => $d['reason']]);
        }, 3);
    }
}
