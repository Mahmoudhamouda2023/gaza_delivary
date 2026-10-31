<?php

namespace Tests\Feature;

use Tests\TestCase;

class StoreAdministrationTest extends TestCase
{
    public function test_admin_approves_store_and_merchant_can_login_to_own_dashboard(): void
    {
        $operator = $this->staff('operations');
        $merchant = $this->merchant();
        $this->asStaff($operator)->get('/admin/stores')->assertOk()->assertSee('متجر الاختبار');
        $this->patch(route('admin.stores.update', $merchant->store), ['status' => 'active', 'reason' => 'تم التحقق من بيانات المتجر'])->assertRedirect(route('admin.stores.show', $merchant->store));
        $this->assertSame('active', $merchant->fresh()->status->value);
        $this->assertSame(2, $merchant->fresh()->session_version);
        $this->assertDatabaseHas('account_audit_logs', ['event' => 'store.status_changed', 'actor_staff_id' => $operator->id, 'subject_store_id' => $merchant->store->id]);
        $this->post('/staff/logout');
        $this->post('/login', ['phone' => $merchant->phone, 'password' => 'MerchantSecure123!'])->assertRedirect('/merchant');
        $this->get('/merchant')->assertOk()->assertSee('متجر الاختبار');
    }

    public function test_rejection_reason_is_visible_only_in_authenticated_owner_status_page(): void
    {
        $operator = $this->staff('operations');
        $merchant = $this->merchant();
        $this->asStaff($operator)->patch(route('admin.stores.update', $merchant->store), ['status' => 'rejected', 'reason' => 'بيانات التواصل تحتاج مراجعة'])->assertRedirect();
        $this->post('/staff/logout');
        $this->post('/login', ['phone' => $merchant->phone, 'password' => 'MerchantSecure123!']);
        $this->get('/merchant')->assertRedirect('/account/status');
        $this->get('/account/status')->assertOk()->assertSee('بيانات التواصل تحتاج مراجعة');
    }

    public function test_finance_staff_can_view_but_cannot_review_store(): void
    {
        $finance = $this->staff('finance');
        $merchant = $this->merchant();
        $this->asStaff($finance)->get(route('admin.stores.show', $merchant->store))->assertOk()->assertDontSee('تأكيد القرار');
        $this->patch(route('admin.stores.update', $merchant->store), ['status' => 'active', 'reason' => 'محاولة غير مخولة'])->assertForbidden();
        $this->assertSame('pending', $merchant->store->fresh()->status->value);
    }

    public function test_captain_and_merchant_cannot_read_store_administration_endpoints(): void
    {
        $merchant = $this->merchant(true);
        $captain = $this->staff('captain');
        $this->asStaff($captain)->get('/admin/stores')->assertForbidden();
        $this->get(route('admin.stores.show', $merchant->store))->assertForbidden();
        $this->post('/staff/logout');
        $this->asMerchant($merchant)->get('/admin/stores')->assertRedirect('/staff/login');
    }

    public function test_merchant_dashboard_does_not_use_a_store_id_from_query_string(): void
    {
        $one = $this->merchant(true, name: 'متجري الشخصي');
        $two = $this->merchant(true, '0592000002', 'متجر شخص آخر');
        $this->asMerchant($one)->get('/merchant?store_id='.$two->store->public_id)->assertOk()->assertSee('متجري الشخصي')->assertDontSee('متجر شخص آخر');
    }

    public function test_review_requires_reason_and_invalid_transitions_do_not_change_state(): void
    {
        $operator = $this->staff('operations');
        $merchant = $this->merchant();
        $this->asStaff($operator)->patch(route('admin.stores.update', $merchant->store), ['status' => 'active'])->assertSessionHasErrors('reason');
        $this->patch(route('admin.stores.update', $merchant->store), ['status' => 'suspended', 'reason' => 'انتقال غير صحيح'])->assertSessionHasErrors('status');
        $this->assertSame('pending', $merchant->store->fresh()->status->value);
    }

    public function test_store_names_and_review_notes_are_html_escaped(): void
    {
        $operator = $this->staff();
        $merchant = $this->merchant(name: '<script>alert(1)</script>');
        $this->asStaff($operator)->get(route('admin.stores.show', $merchant->store))->assertOk()->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
    }

    public function test_yajra_endpoint_paginates_without_exposing_owner_secrets(): void
    {
        $operator = $this->staff('operations');
        for ($i = 1; $i <= 12; $i++) {
            $this->merchant(phone: '059200'.str_pad((string) $i, 4, '0', STR_PAD_LEFT), name: 'متجر '.$i);
        }
        $response = $this->asStaff($operator)->getJson('/admin/stores/data?draw=1&start=0&length=10&search[value]=&search[regex]=false');
        $response->assertOk()->assertJsonPath('recordsTotal', 12)->assertJsonCount(10, 'data');
        $this->assertSame(['public_id', 'name', 'status_label', 'created_at', 'details_url'], array_keys($response->json('data.0')));
        $response->assertDontSee('password')->assertDontSee('remember_token')->assertDontSee('session_version');
    }

    public function test_yajra_endpoint_rejects_unbounded_pages_and_regex_search(): void
    {
        $operator = $this->staff('operations');
        $this->asStaff($operator)->getJson('/admin/stores/data?draw=1&start=0&length=-1')->assertUnprocessable()->assertJsonValidationErrors('length');
        $this->getJson('/admin/stores/data?draw=1&start=0&length=10&search[regex]=true')->assertUnprocessable()->assertJsonValidationErrors('search.regex');
    }
}
