<?php

namespace Tests;

use GazaDelivery\Foundation\Models\CaptainProfile;
use GazaDelivery\Foundation\Models\Staff;
use GazaDelivery\Foundation\Models\User;
use GazaDelivery\Foundation\Services\MerchantRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);
    }

    protected function staff(string $role = 'super_admin', string $phone = '0591000001', bool $temporary = false): Staff
    {
        $staff = new Staff;
        $staff->forceFill(['name' => 'موظف تجريبي', 'phone' => $phone, 'password' => 'SecureTemporary123!', 'status' => 'active', 'must_change_password' => $temporary])->save();
        $staff->assignRole($role);
        if ($role === 'captain') {
            $profile = new CaptainProfile;
            $profile->forceFill(['staff_id' => $staff->getKey(), 'vehicle_type' => 'bicycle'])->save();
        }

        return $staff->fresh();
    }

    protected function merchant(bool $active = false, string $phone = '0592000001', string $name = 'متجر الاختبار'): User
    {
        $user = app(MerchantRegistration::class)->register([
            'name' => 'تاجر تجريبي', 'phone' => $phone, 'store_name' => $name,
            'password' => 'MerchantSecure123!', 'password_confirmation' => 'MerchantSecure123!',
        ]);
        if ($active) {
            $user->forceFill(['status' => 'active'])->save();
            $user->store->forceFill(['status' => 'active'])->save();
        }

        return $user->fresh()->load('store');
    }

    protected function asStaff(Staff $staff): static
    {
        return $this->actingAs($staff, 'staff')->withSession(['delivery_auth.staff.version' => $staff->session_version]);
    }

    protected function asMerchant(User $user): static
    {
        return $this->actingAs($user, 'merchant')->withSession(['delivery_auth.merchant.version' => $user->session_version]);
    }
}
