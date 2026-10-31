<?php

declare(strict_types=1);

namespace GazaDelivery\Foundation\Tests;

use GazaDelivery\Foundation\Enums\AccountStatus;
use GazaDelivery\Foundation\Enums\StoreStatus;
use GazaDelivery\Foundation\Models\AccountAuditLog;
use GazaDelivery\Foundation\Models\Staff;
use GazaDelivery\Foundation\Models\Store;
use GazaDelivery\Foundation\Models\User;
use GazaDelivery\Foundation\Support\PhoneNumber;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class IdentityTest extends TestCase
{
    public function test_registration_creates_pending_owner_and_store_and_hashes_password(): void
    {
        $user = $this->registration();
        $this->assertSame(AccountStatus::Pending, $user->status);
        $this->assertSame(StoreStatus::Pending, $user->store->status);
        $this->assertSame('+970591234567', $user->phone);
        $this->assertTrue(Hash::check('MerchantPassword123!', $user->password));
        $this->assertArrayNotHasKey('password', $user->toArray());
        $this->assertSame(2, AccountAuditLog::query()->count());
    }

    public function test_public_registration_cannot_set_status_owner_id_or_staff_roles(): void
    {
        $user = $this->registration(['status' => 'active', 'owner_user_id' => 777, 'roles' => ['super_admin']]);
        $this->assertSame(AccountStatus::Pending, $user->status);
        $this->assertSame($user->getKey(), $user->store->owner_user_id);
        $this->assertSame(0, Staff::query()->count());
        $this->assertSame(0, DB::table('model_has_roles')->count());
    }

    public function test_equivalent_phone_formats_cannot_create_duplicate_merchants(): void
    {
        $this->registration();
        $this->expectException(ValidationException::class);
        $this->registration(['phone' => '00970 59 123 4567']);
    }

    public function test_arabic_phone_digits_are_normalized_and_country_code_is_preserved(): void
    {
        $this->assertSame('+970591234567', PhoneNumber::normalize('٠٥٩١٢٣٤٥٦٧'));
        $this->assertSame('+972591234567', PhoneNumber::normalize('+972-59-123-4567'));
    }

    public function test_invalid_phone_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        PhoneNumber::normalize('not-a-phone');
    }

    public function test_same_phone_can_belong_to_staff_and_merchant_with_separate_providers(): void
    {
        $staff = $this->staff('captain', '0591234567');
        $user = $this->registration();
        $this->assertSame($staff->phone, $user->phone);
        $this->assertSame(Staff::class, config('auth.providers.delivery_staff.model'));
        $this->assertSame(User::class, config('auth.providers.delivery_merchants.model'));
        $this->assertNotSame(auth('staff')->getProvider(), auth('merchant')->getProvider());
    }

    public function test_failed_audit_rolls_back_the_entire_registration(): void
    {
        AccountAuditLog::creating(fn () => throw new RuntimeException('Simulated audit storage failure'));
        try {
            $this->registration();
            $this->fail('The transaction should fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated audit storage failure', $exception->getMessage());
        }
        $this->assertSame(0, User::query()->count());
        $this->assertSame(0, Store::query()->count());
    }

    public function test_password_confirmation_failure_creates_no_accounts(): void
    {
        try {
            $this->registration(['password_confirmation' => 'different']);
            $this->fail('Invalid confirmation was accepted.');
        } catch (ValidationException) {
            $this->assertSame(0, User::query()->count());
        }
    }

    public function test_foreign_key_prevents_deleting_a_merchant_with_a_store(): void
    {
        $user = $this->registration();
        $this->expectException(QueryException::class);
        $user->delete();
    }

    public function test_audit_does_not_contain_passwords(): void
    {
        $this->registration();
        $serialized = AccountAuditLog::query()->get()->toJson();
        $this->assertStringNotContainsString('MerchantPassword123!', $serialized);
        $this->assertStringNotContainsString('password', $serialized);
    }
}
