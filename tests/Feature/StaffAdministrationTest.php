<?php

namespace Tests\Feature;

use GazaDelivery\Foundation\Models\Staff;
use Tests\TestCase;

class StaffAdministrationTest extends TestCase
{
    public function test_super_admin_can_create_a_captain_that_requires_password_change(): void
    {
        $admin = $this->staff();
        $this->asStaff($admin)->post('/admin/staff', [
            'name' => 'كابتن جديد', 'phone' => '0591000002', 'roles' => ['captain'], 'vehicle_type' => 'bicycle',
            'password' => 'SecureTemporary123!', 'password_confirmation' => 'SecureTemporary123!',
        ])->assertRedirect('/admin/staff');
        $captain = Staff::query()->where('phone', '0591000002')->firstOrFail();
        $this->assertTrue($captain->must_change_password);
        $this->assertTrue($captain->hasRole('captain'));
        $this->assertSame('bicycle', $captain->captainProfile->vehicle_type);
    }

    public function test_operations_cannot_provision_accounts_or_escalate_roles(): void
    {
        $operator = $this->staff('operations');
        $this->asStaff($operator)->get('/admin/staff')->assertOk()->assertDontSee('إضافة حساب');
        $this->get('/admin/staff/create')->assertForbidden();
        $this->post('/admin/staff', [
            'name' => 'محاولة', 'phone' => '0591000002', 'roles' => ['super_admin'],
            'password' => 'SecureTemporary123!', 'password_confirmation' => 'SecureTemporary123!',
        ])->assertForbidden();
        $this->assertDatabaseCount('staff', 1);
    }

    public function test_last_admin_cannot_suspend_themself(): void
    {
        $admin = $this->staff();
        $this->asStaff($admin)->patch(route('admin.staff.suspend', $admin), ['reason' => 'اختبار المشرف الأخير'])->assertSessionHasErrors('staff');
        $this->assertTrue($admin->fresh()->isActive());
    }

    public function test_suspended_captain_is_logged_out_on_next_request(): void
    {
        $admin = $this->staff();
        $captain = $this->staff('captain', '0591000002');
        $this->asStaff($admin)->patch(route('admin.staff.suspend', $captain), ['reason' => 'تعليق حساب تجريبي'])->assertRedirect('/admin/staff');
        $this->asStaff($captain)->get('/captain')->assertRedirect('/staff/login');
        $this->assertGuest('staff');
    }

    public function test_captain_role_requires_vehicle_type(): void
    {
        $admin = $this->staff();
        $this->asStaff($admin)->post('/admin/staff', [
            'name' => 'بدون وسيلة', 'phone' => '0591000002', 'roles' => ['captain'],
            'password' => 'SecureTemporary123!', 'password_confirmation' => 'SecureTemporary123!',
        ])->assertSessionHasErrors('vehicle_type');
        $this->assertDatabaseCount('staff', 1);
    }
}
