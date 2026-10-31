<?php

declare(strict_types=1);

namespace GazaDelivery\Foundation\Tests;

use GazaDelivery\Foundation\Enums\StoreStatus;
use GazaDelivery\Foundation\Services\StoreReview;

final class SessionBoundaryTest extends TestCase
{
    public function test_active_staff_with_valid_version_can_access_protected_route(): void
    {
        $staff = $this->staff('operations');
        $this->actingAs($staff, 'staff')->withSession(['delivery_auth.staff.version' => 1])
            ->getJson('/test/staff')->assertOk();
    }

    public function test_staff_session_does_not_authenticate_merchant_guard(): void
    {
        $staff = $this->staff('operations');
        $this->actingAs($staff, 'staff')->withSession(['delivery_auth.staff.version' => 1])
            ->getJson('/test/merchant')->assertUnauthorized();
    }

    public function test_merchant_session_does_not_authenticate_staff_guard(): void
    {
        $user = $this->registration();
        $this->actingAs($user, 'merchant')->getJson('/test/staff')->assertUnauthorized();
    }

    public function test_pending_merchant_is_denied_even_when_session_is_present(): void
    {
        $user = $this->registration();
        $this->actingAs($user, 'merchant')->withSession(['delivery_auth.merchant.version' => 1])
            ->getJson('/test/merchant')->assertUnauthorized();
    }

    public function test_stale_session_is_denied_after_store_suspension(): void
    {
        $user = $this->registration();
        $operator = $this->staff('operations');
        $review = app(StoreReview::class);
        $review->transition($operator, $user->store, StoreStatus::Active, 'مراجعة تجريبية');
        $user = $user->fresh();
        $version = $user->session_version;
        $review->transition($operator, $user->store->fresh(), StoreStatus::Suspended, 'تعليق تجريبي');
        $this->actingAs($user, 'merchant')->withSession(['delivery_auth.merchant.version' => $version])
            ->getJson('/test/merchant')->assertUnauthorized();
    }

    public function test_missing_session_version_is_not_silently_accepted(): void
    {
        $staff = $this->staff('operations');
        $this->actingAs($staff, 'staff')->getJson('/test/staff')->assertUnauthorized();
    }
}
