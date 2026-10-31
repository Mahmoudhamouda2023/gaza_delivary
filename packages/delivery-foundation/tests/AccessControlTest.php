<?php

declare(strict_types=1);

namespace GazaDelivery\Foundation\Tests;

use GazaDelivery\Foundation\Enums\StoreStatus;
use GazaDelivery\Foundation\Models\Staff;
use GazaDelivery\Foundation\Services\StaffAccounts;
use GazaDelivery\Foundation\Services\StoreDirectory;
use GazaDelivery\Foundation\Services\StoreReview;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class AccessControlTest extends TestCase
{
    public function test_merchant_cannot_read_another_store_by_public_id(): void
    {
        $one = $this->registration();
        $two = $this->registration(['phone' => '0591234568']);
        $this->assertTrue(Gate::forUser($one)->allows('view', $one->store));
        $this->assertFalse(Gate::forUser($one)->allows('view', $two->store));
        $this->assertSame(1, app(StoreDirectory::class)->queryFor($one)->count());
        $this->expectException(ModelNotFoundException::class);
        app(StoreDirectory::class)->findFor($one, $two->store->public_id);
    }

    public function test_operations_cannot_approve_financial_settlements(): void
    {
        $staff = $this->staff('operations');
        $this->assertTrue($staff->hasPermissionTo('stores.review', 'staff'));
        $this->assertFalse($staff->hasPermissionTo('finance.settlements.approve', 'staff'));
        $this->assertFalse($staff->hasPermissionTo('pricing.manage', 'staff'));
    }

    public function test_captain_cannot_browse_all_merchants(): void
    {
        $captain = $this->staff('captain');
        $this->expectException(AuthorizationException::class);
        app(StoreDirectory::class)->queryFor($captain);
    }

    public function test_merchant_and_captain_cannot_activate_a_store(): void
    {
        $merchant = $this->registration();
        $captain = $this->staff('captain');
        $this->assertFalse(Gate::forUser($merchant)->allows('review', $merchant->store));
        $this->expectException(AuthorizationException::class);
        app(StoreReview::class)->transition($captain, $merchant->store, StoreStatus::Active, 'مراجعة تجريبية');
    }

    public function test_review_activates_owner_and_store_and_revokes_old_sessions(): void
    {
        $merchant = $this->registration();
        $operator = $this->staff('operations');
        app(StoreReview::class)->transition($operator, $merchant->store, StoreStatus::Active, 'تمت مراجعة بيانات المتجر');
        $this->assertSame('active', $merchant->fresh()->status->value);
        $this->assertSame(StoreStatus::Active, $merchant->store->fresh()->status);
        $this->assertSame(2, $merchant->fresh()->session_version);
    }

    public function test_pending_store_cannot_be_suspended_without_review(): void
    {
        $merchant = $this->registration();
        $operator = $this->staff('operations');
        $this->expectException(ValidationException::class);
        app(StoreReview::class)->transition($operator, $merchant->store, StoreStatus::Suspended, 'سبب تجريبي');
    }

    public function test_suspended_staff_cannot_review_even_with_permission(): void
    {
        $merchant = $this->registration();
        $operator = $this->staff('operations');
        $operator->forceFill(['status' => 'suspended'])->save();
        $this->expectException(AuthorizationException::class);
        app(StoreReview::class)->transition($operator, $merchant->store, StoreStatus::Active, 'سبب تجريبي');
    }

    public function test_only_super_admin_can_create_staff_and_assign_roles(): void
    {
        $operator = $this->staff('operations');
        $this->expectException(AuthorizationException::class);
        app(StaffAccounts::class)->provision($operator, [
            'name' => 'Unauthorized', 'phone' => '0591234568', 'roles' => ['super_admin'],
            'password' => 'StaffPassword123!', 'password_confirmation' => 'StaffPassword123!',
        ]);
    }

    public function test_super_admin_can_create_captain_with_profile_and_forced_password_change(): void
    {
        $admin = $this->staff('super_admin');
        $captain = app(StaffAccounts::class)->provision($admin, [
            'name' => 'أحمد تجريبي', 'phone' => '0591234568', 'roles' => ['captain'], 'vehicle_type' => 'bicycle',
            'password' => 'StaffPassword123!', 'password_confirmation' => 'StaffPassword123!',
        ]);
        $this->assertTrue($captain->hasRole('captain', 'staff'));
        $this->assertSame('bicycle', $captain->captainProfile->vehicle_type);
        $this->assertTrue($captain->must_change_password);
        $this->assertSame(2, Staff::query()->count());
    }

    public function test_last_active_super_admin_cannot_be_suspended(): void
    {
        $admin = $this->staff('super_admin');
        $this->expectException(ValidationException::class);
        app(StaffAccounts::class)->suspend($admin, $admin, 'اختبار حماية المشرف الأخير');
    }

    public function test_bootstrap_command_does_not_create_an_additional_admin(): void
    {
        $this->staff('super_admin');
        $this->artisan('delivery:bootstrap-admin')
            ->expectsOutput('A super administrator already exists. Use the authorized staff management service.')
            ->assertExitCode(1);
        $this->assertSame(1, Staff::query()->count());
    }

    public function test_suspension_revokes_session_version_and_preserves_account(): void
    {
        $admin = $this->staff('super_admin');
        $captain = $this->staff('captain', '0591234568');
        app(StaffAccounts::class)->suspend($admin, $captain, 'إيقاف تجريبي');
        $this->assertSame('suspended', $captain->fresh()->status->value);
        $this->assertSame(2, $captain->fresh()->session_version);
        $this->assertSame(2, Staff::query()->count());
    }
}
