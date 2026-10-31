<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use GazaDelivery\Foundation\Models\Staff;
use GazaDelivery\Foundation\Services\StaffAccounts;
use Illuminate\Http\Request;

final class StaffController extends Controller
{
    public function index()
    {
        return view('admin.staff.index', ['staff' => Staff::query()->with('roles')->latest('id')->paginate(15)]);
    }

    public function create()
    {
        return view('admin.staff.create');
    }

    public function store(Request $request, StaffAccounts $accounts)
    {
        $accounts->provision($request->user('staff'), $request->only(['name', 'phone', 'email', 'password', 'password_confirmation', 'roles', 'vehicle_type']));

        return redirect()->route('admin.staff.index')->with('status', 'تم إنشاء الحساب. يجب على صاحبه تغيير كلمة المرور عند أول دخول.');
    }

    public function suspend(Request $request, Staff $staff, StaffAccounts $accounts)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:500']]);
        $accounts->suspend($request->user('staff'), $staff, $data['reason']);

        return redirect()->route('admin.staff.index')->with('status', 'تم إيقاف الحساب وإبطال جلساته.');
    }
}
