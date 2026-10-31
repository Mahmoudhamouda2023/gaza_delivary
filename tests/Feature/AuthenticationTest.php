<?php

namespace Tests\Feature;

use GazaDelivery\Foundation\Models\Staff;
use GazaDelivery\Foundation\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    public function test_public_pages_render_without_javascript_or_external_dependencies(): void
    {
        foreach (['/', '/login', '/staff/login', '/register'] as $url) {
            $response = $this->get($url)->assertOk();
            $response->assertSee('lang="ar"', false)->assertDontSee('<script', false);
            $response->assertHeader('X-Frame-Options', 'DENY');
            $response->assertHeader('X-Content-Type-Options', 'nosniff');
        }
    }

    public function test_guests_are_directed_to_the_matching_login_portal(): void
    {
        $this->get('/admin')->assertRedirect('/staff/login');
        $this->get('/captain')->assertRedirect('/staff/login');
        $this->get('/merchant')->assertRedirect('/login');
    }

    public function test_registration_creates_only_pending_merchant_and_opens_status_page(): void
    {
        $this->post('/register', [
            'name' => 'محمد', 'store_name' => 'متجر جديد', 'phone' => '0592223333',
            'password' => 'MerchantSecure123!', 'password_confirmation' => 'MerchantSecure123!',
            'status' => 'active', 'roles' => ['super_admin'], 'owner_user_id' => 99,
        ])->assertRedirect('/account/status');
        $user = User::query()->firstOrFail();
        $this->assertAuthenticatedAs($user, 'merchant');
        $this->assertSame('pending', $user->status->value);
        $this->assertSame('pending', $user->store->status->value);
        $this->assertSame(0, Staff::query()->count());
        $this->get('/merchant')->assertRedirect('/account/status');
        $this->get('/account/status')->assertOk()->assertSee('متجر جديد');
    }

    public function test_equivalent_phone_registration_is_rejected_without_duplicate_store(): void
    {
        $this->merchant();
        $this->post('/register', [
            'name' => 'ثانٍ', 'store_name' => 'تكرار', 'phone' => '00970 59 200 0001',
            'password' => 'MerchantSecure123!', 'password_confirmation' => 'MerchantSecure123!',
        ])->assertSessionHasErrors('phone');
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('stores', 1);
    }

    public function test_staff_login_chooses_admin_or_captain_from_server_roles(): void
    {
        $staff = $this->staff('operations');
        $this->post('/staff/login', ['phone' => $staff->phone, 'password' => 'SecureTemporary123!', 'role' => 'super_admin'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($staff, 'staff');
        $this->assertGuest('merchant');
        $this->get('/admin')->assertOk();
        $this->post('/staff/logout')->assertRedirect('/staff/login');
        $captain = $this->staff('captain', '0591000002');
        $this->post('/staff/login', ['phone' => $captain->phone, 'password' => 'SecureTemporary123!', 'role' => 'super_admin'])->assertRedirect('/captain');
        $this->get('/captain')->assertOk();
        $this->get('/admin')->assertForbidden();
    }

    public function test_temporary_password_must_be_changed_before_any_work_route(): void
    {
        $staff = $this->staff('captain', temporary: true);
        $this->post('/staff/login', ['phone' => $staff->phone, 'password' => 'SecureTemporary123!'])->assertRedirect('/staff/password');
        $this->get('/captain')->assertRedirect('/staff/password');
        $this->get('/staff/password')->assertOk();
        $this->put('/staff/password', [
            'current_password' => 'SecureTemporary123!', 'password' => 'MyNewPersonal123!', 'password_confirmation' => 'MyNewPersonal123!',
        ])->assertRedirect('/captain');
        $this->assertFalse($staff->fresh()->must_change_password);
        $this->assertTrue(Hash::check('MyNewPersonal123!', $staff->fresh()->password));
        $this->assertSame(2, $staff->fresh()->session_version);
        $this->get('/captain')->assertOk();
    }

    public function test_wrong_current_password_cannot_change_the_password(): void
    {
        $staff = $this->staff();
        $this->asStaff($staff)->put('/staff/password', [
            'current_password' => 'wrong', 'password' => 'MyNewPersonal123!', 'password_confirmation' => 'MyNewPersonal123!',
        ])->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('SecureTemporary123!', $staff->fresh()->password));
    }

    public function test_same_password_cannot_be_reused_for_forced_change(): void
    {
        $staff = $this->staff('captain', temporary: true);
        $this->asStaff($staff)->put('/staff/password', [
            'current_password' => 'SecureTemporary123!', 'password' => 'SecureTemporary123!', 'password_confirmation' => 'SecureTemporary123!',
        ])->assertSessionHasErrors('password');
        $this->assertTrue($staff->fresh()->must_change_password);
    }

    public function test_login_guards_are_separate_even_for_same_phone(): void
    {
        $staff = $this->staff(phone: '0592000001');
        $merchant = $this->merchant(true);
        $this->post('/login', ['phone' => $merchant->phone, 'password' => 'SecureTemporary123!'])->assertSessionHasErrors('phone');
        $this->assertGuest('merchant');
        $this->post('/login', ['phone' => $merchant->phone, 'password' => 'MerchantSecure123!'])->assertRedirect('/merchant');
        $this->assertAuthenticatedAs($merchant, 'merchant');
        $this->assertGuest('staff');
        $this->get('/admin')->assertRedirect('/staff/login');
    }

    public function test_suspended_staff_cannot_login(): void
    {
        $staff = $this->staff();
        $staff->forceFill(['status' => 'suspended'])->save();
        $this->post('/staff/login', ['phone' => $staff->phone, 'password' => 'SecureTemporary123!'])->assertSessionHasErrors('phone');
        $this->assertGuest('staff');
    }

    public function test_failed_logins_are_throttled(): void
    {
        $this->staff();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/staff/login', ['phone' => '0591000001', 'password' => 'wrong'])->assertSessionHasErrors('phone');
        }
        $this->post('/staff/login', ['phone' => '0591000001', 'password' => 'SecureTemporary123!'])->assertStatus(429);
        $this->assertGuest('staff');
    }

    public function test_logout_invalidates_access_and_session_version_key(): void
    {
        $staff = $this->staff();
        $this->asStaff($staff)->post('/staff/logout')->assertRedirect('/staff/login');
        $this->assertGuest('staff');
        $this->assertFalse(session()->has('delivery_auth.staff.version'));
        $this->get('/admin')->assertRedirect('/staff/login');
    }

    public function test_a_stale_session_is_rejected_after_password_change_elsewhere(): void
    {
        $staff = $this->staff();
        $this->asStaff($staff);
        $staff->forceFill(['session_version' => 2])->save();
        $this->get('/admin')->assertRedirect('/staff/login');
        $this->assertGuest('staff');
    }

    public function test_registration_validation_does_not_flash_passwords(): void
    {
        $this->from('/register')->post('/register', [
            'name' => 'اختبار', 'phone' => '0593334444', 'store_name' => 'اختبار',
            'password' => 'SensitiveSecret123!', 'password_confirmation' => 'different',
        ])->assertSessionHasErrors('password')->assertSessionMissing('_old_input.password')->assertSessionMissing('_old_input.password_confirmation');
    }
}
