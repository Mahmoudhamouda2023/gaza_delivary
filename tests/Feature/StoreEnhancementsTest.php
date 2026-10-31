<?php

namespace Tests\Feature;

use App\Models\FinancialEntry;
use App\Models\Neighborhood;
use App\Models\Order;
use App\Models\PickupAddress;
use App\Models\StoreAgreement;
use App\Models\Tariff;
use App\Models\Zone;
use App\Services\Delivery\Finance;
use App\Services\Delivery\OrderWorkflow;
use App\Services\Delivery\Payments;
use GazaDelivery\Foundation\Models\Staff;
use GazaDelivery\Foundation\Models\User;
use GazaDelivery\Foundation\Support\PhoneNumber;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class StoreEnhancementsTest extends TestCase
{
    private User $owner;

    private Staff $admin;

    private Staff $captain;

    private Zone $from;

    private Zone $to;

    private PickupAddress $address;

    private Tariff $tariff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = $this->merchant(true);
        $this->admin = $this->staff();
        $this->captain = $this->staff('captain', '0591000002');
        $this->from = $this->make(Zone::class, ['name' => 'غزة', 'active' => true]);
        $this->to = $this->make(Zone::class, ['name' => 'الشمال', 'active' => true]);
        $this->address = $this->make(PickupAddress::class, ['store_id' => $this->owner->store->id, 'zone_id' => $this->from->id, 'label' => 'المتجر', 'address' => 'العنوان التفصيلي', 'contact_phone' => '0591000009', 'active' => true]);
        $this->tariff = $this->make(Tariff::class, ['from_zone_id' => $this->from->id, 'to_zone_id' => $this->to->id, 'fee_minor' => 1000, 'captain_pay_minor' => 600, 'return_fee_minor' => 300, 'captain_return_pay_minor' => 200, 'active' => true]);
    }

    private function make(string $type, array $data)
    {
        $m = new $type;
        $m->forceFill($data)->save();

        return $m->fresh();
    }

    private function data(array $extra = []): array
    {
        return array_replace(['idempotency_key' => (string) Str::uuid(), 'pickup_address_id' => $this->address->id, 'to_zone_id' => $this->to->id, 'recipient_name' => 'مستلم', 'recipient_phone' => '0593334444', 'recipient_address' => 'عنوان مستلم تجريبي', 'description' => 'ملابس', 'fee_payer' => 'merchant', 'goods_amount' => '100'], $extra);
    }

    private function ready(array $extra = []): Order
    {
        $w = app(OrderWorkflow::class);
        $o = $w->create($this->owner, $this->data($extra));
        $o = $w->assign($this->admin, $o, $this->captain->id);
        $o = $w->transition($this->captain, $o, 'picked_up');

        return $w->transition($this->captain, $o, 'out_for_delivery');
    }

    private function receipt(string $method = 'jawwal_pay', string $recipient = 'captain', string $reference = 'REF100'): array
    {
        return ['payment_method' => $method, 'payment_recipient' => $recipient, 'payment_account' => '0591234567', 'payment_reference' => $reference];
    }

    public function test_eight_character_password_and_arabic_local_phone_register_successfully(): void
    {
        $this->post('/register', ['name' => 'تاجر', 'store_name' => 'متجر', 'phone' => '٠٥٩١٢٣٤٥٦٧', 'password' => 'Abcd1234', 'password_confirmation' => 'Abcd1234'])->assertRedirect('/account/status');
        $this->assertDatabaseHas('users', ['phone' => '0591234567']);
        $this->assertSame('0593334444', PhoneNumber::normalize('0593334444'));
    }

    public static function invalidPhones(): array
    {
        return [['059123456'], ['05912345678'], ['+970591234567'], ['00970591234567'], ['1234567890']];
    }

    #[DataProvider('invalidPhones')]
    public function test_registration_rejects_nonlocal_or_wrong_length_phone(string $phone): void
    {
        $this->post('/register', ['name' => 'تاجر', 'store_name' => 'متجر', 'phone' => $phone, 'password' => 'Abcd1234', 'password_confirmation' => 'Abcd1234'])->assertSessionHasErrors('phone');
    }

    public function test_seven_character_password_is_rejected(): void
    {
        $this->post('/register', ['name' => 'تاجر', 'store_name' => 'متجر', 'phone' => '0591234567', 'password' => 'Abc1234', 'password_confirmation' => 'Abc1234'])->assertSessionHasErrors('password');
    }

    public function test_admin_provisions_electric_bicycle_captain_with_eight_character_password(): void
    {
        $this->asStaff($this->admin)->post(route('admin.staff.store'), ['name' => 'كابتن', 'phone' => '0597778888', 'password' => 'Abcd1234', 'password_confirmation' => 'Abcd1234', 'roles' => ['captain'], 'vehicle_type' => 'electric_bicycle'])->assertSessionHasNoErrors()->assertRedirect();
        $s = Staff::where('phone', '0597778888')->firstOrFail();
        $this->assertSame('electric_bicycle', $s->captainProfile->vehicle_type);
        $this->post(route('admin.staff.store'), ['name' => 'كابتن', 'phone' => '0597778889', 'password' => 'Abcd1234', 'password_confirmation' => 'Abcd1234', 'roles' => ['captain'], 'vehicle_type' => 'car'])->assertSessionHasErrors('vehicle_type');
    }

    public function test_password_change_accepts_eight_characters(): void
    {
        $this->asStaff($this->captain)->put('/staff/password', ['current_password' => 'SecureTemporary123!', 'password' => 'Abcd1234', 'password_confirmation' => 'Abcd1234'])->assertSessionHasNoErrors()->assertRedirect();
    }

    public function test_scheduled_order_stores_utc_and_displays_palestine_time(): void
    {
        $date = now('Asia/Hebron')->addDay()->setTime(15, 0);
        $o = app(OrderWorkflow::class)->create($this->owner, $this->data(['delivery_mode' => 'scheduled', 'scheduled_for' => $date->format('Y-m-d\TH:i')]));
        $this->assertSame($date->copy()->utc()->format('Y-m-d H:i'), $o->fresh()->scheduled_for->format('Y-m-d H:i'));
        $this->assertStringContainsString('15:00', $o->fresh()->delivery_time_label);
        $this->asMerchant($this->owner)->get(route('merchant.orders.show', $o))->assertOk()->assertSee('15:00');
        $this->get(route('merchant.orders.index', ['scheduled' => '1']))->assertSee($o->recipient_name);
    }

    public function test_past_schedule_cannot_create_order(): void
    {
        $d = $this->data(['delivery_mode' => 'scheduled', 'scheduled_for' => now('Asia/Hebron')->subDay()->format('Y-m-d\TH:i')]);
        $this->asMerchant($this->owner)->post(route('merchant.orders.preview'), $d)->assertSessionHasErrors('scheduled_for');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_now_ignores_an_old_scheduled_value(): void
    {
        $o = app(OrderWorkflow::class)->create($this->owner, $this->data(['delivery_mode' => 'now', 'scheduled_for' => '2020-01-01T15:00']));
        $this->assertNull($o->scheduled_for);
    }

    public function test_neighborhood_is_required_for_configured_zone_and_cannot_cross_zones(): void
    {
        $n = $this->make(Neighborhood::class, ['zone_id' => $this->to->id, 'name' => 'حي المستلم', 'active' => true]);
        $wrong = $this->make(Neighborhood::class, ['zone_id' => $this->from->id, 'name' => 'حي آخر', 'active' => true]);
        $this->asMerchant($this->owner)->post(route('merchant.orders.preview'), $this->data())->assertSessionHasErrors('to_neighborhood_id');
        $this->post(route('merchant.orders.preview'), $this->data(['to_neighborhood_id' => $wrong->id]))->assertSessionHasErrors('to_neighborhood_id');
        $o = app(OrderWorkflow::class)->create($this->owner, $this->data(['to_neighborhood_id' => $n->id]));
        $n->forceFill(['name' => 'اسم محدث'])->save();
        $this->assertStringContainsString('حي المستلم', $o->fresh()->destination_snapshot);
    }

    public function test_admin_manages_neighborhoods_and_merchants_cannot(): void
    {
        $this->asStaff($this->admin)->post(route('admin.catalog.store', 'neighborhoods'), ['zone_id' => $this->to->id, 'name' => 'حي جديد', 'active' => '1'])->assertSessionHasNoErrors()->assertRedirect();
        $n = Neighborhood::firstOrFail();
        $this->patch(route('admin.catalog.update', ['neighborhoods', $n->id]), ['zone_id' => $this->to->id, 'name' => 'الاسم الجديد', 'active' => '0'])->assertSessionHasNoErrors();
        $this->assertFalse($n->fresh()->active);
        $this->asStaff($this->captain)->post(route('admin.catalog.store', 'zones'), ['name' => 'ممنوع', 'active' => '1'])->assertForbidden();
    }

    public function test_percentage_uses_final_fee_and_is_snapshotted(): void
    {
        $this->tariff->forceFill(['captain_pay_mode' => 'percent', 'captain_share_bps' => 7000])->save();
        $this->make(StoreAgreement::class, ['store_id' => $this->owner->store->id, 'discount_bps' => 1000, 'starts_on' => today()->toDateString(), 'active' => true]);
        $o = app(OrderWorkflow::class)->create($this->owner, $this->data());
        $this->assertSame(900, (int) $o->fee_minor);
        $this->assertSame(630, (int) $o->captain_pay_minor);
        $this->tariff->forceFill(['captain_share_bps' => 8000])->save();
        $this->assertSame(630, (int) $o->fresh()->captain_pay_minor);
    }

    public function test_pricing_study_computes_break_even_without_changing_tariffs(): void
    {
        $this->asStaff($this->admin)->get(route('admin.pricing-study', ['fee' => '10', 'captain_percent' => '70', 'variable_cost' => '0.50', 'fixed_cost' => '1000', 'monthly_orders' => '500']))->assertOk()->assertViewHas('breakEven', 400)->assertViewHas('profit', 25000);
        $this->assertSame('fixed', $this->tariff->fresh()->captain_pay_mode);
        $this->asStaff($this->captain)->get(route('admin.pricing-study'))->assertForbidden();
    }

    public static function digitalChannels(): array
    {
        $cases = [];
        foreach (['jawwal_pay', 'palpay', 'bank'] as $method) {
            foreach (['captain', 'merchant'] as $to) {
                $cases[$method.'-'.$to] = [$method, $to];
            }
        }

        return $cases;
    }

    #[DataProvider('digitalChannels')]
    public function test_digital_collection_requires_review_and_does_not_double_count(string $method, string $to): void
    {
        $o = $this->ready();
        $this->asStaff($this->captain)->patch(route('captain.orders.status', $o), ['status' => 'delivered', 'collected_amount' => '100'] + $this->receipt($method, $to))->assertSessionHasNoErrors()->assertRedirect();
        $o->refresh();
        $this->assertSame('delivered', $o->status);
        $this->assertSame('pending', $o->payment->status);
        $e = $o->entry;
        $this->assertSame($to === 'captain' ? 10000 : 0, (int) $e->collected_minor);
        $this->assertSame($to === 'merchant' ? 10000 : 0, (int) $e->merchant_received_minor);
        $this->assertSame(0, FinancialEntry::cleared()->count());
        $this->asStaff($this->admin)->patch(route('admin.payments.review', $o->payment), ['decision' => 'approved', 'review_note' => 'تمت مطابقة المبلغ والحساب'])->assertSessionHasNoErrors();
        if ($to === 'captain') {
            $f = app(Finance::class);
            $r = $f->requestRemittance($this->captain);
            $this->assertSame(10000, (int) $r->amount_minor);
            $f->reviewRemittance($this->admin, $r, true, 'استلام مرجع', 10000);
        } else {
            $this->assertSame(-1000, (int) $e->merchant_net_minor);
        }
        $this->assertSame(1, FinancialEntry::cleared()->count());
        $f = app(Finance::class);
        $f->settle($this->admin, $this->owner->store, 'تسوية مكتملة', $to === 'captain' ? 9000 : -1000, Finance::fingerprint([$e->id]));
        $f->payCaptain($this->admin, $this->captain, 'صرف الأجرة', 600, Finance::fingerprint([$e->id]));
        $this->assertDatabaseCount('financial_entries', 1);
    }

    public function test_reference_is_required_and_no_delivery_is_recorded_on_failure(): void
    {
        $o = $this->ready();
        $d = $this->receipt();
        $d['payment_reference'] = '';
        $this->asStaff($this->captain)->patch(route('captain.orders.status', $o), ['status' => 'delivered', 'collected_amount' => '100'] + $d)->assertSessionHasErrors('payment_reference');
        $this->assertSame('out_for_delivery', $o->fresh()->status);
        $this->assertDatabaseCount('financial_entries', 0);
    }

    public function test_reusing_same_receipt_for_second_order_rolls_back_delivery(): void
    {
        $w = app(OrderWorkflow::class);
        $o = $this->ready();
        $w->transition($this->captain, $o, 'delivered', null, 10000, $this->receipt());
        $other = $this->ready();
        $this->asStaff($this->captain)->patch(route('captain.orders.status', $other), ['status' => 'delivered', 'collected_amount' => '100'] + $this->receipt())->assertSessionHasErrors('payment_reference');
        $this->assertSame('out_for_delivery', $other->fresh()->status);
        $this->assertDatabaseCount('order_payments', 1);
    }

    public function test_captain_cannot_approve_payment_and_can_correct_only_rejected_receipt(): void
    {
        $w = app(OrderWorkflow::class);
        $o = $this->ready();
        $w->transition($this->captain, $o, 'delivered', null, 10000, $this->receipt());
        $p = $o->payment()->firstOrFail();
        $this->asStaff($this->captain)->patch(route('admin.payments.review', $p), ['decision' => 'approved', 'review_note' => 'غير مصرح'])->assertForbidden();
        $this->patch(route('captain.payments.correct', $p), $this->receipt('bank', 'merchant', 'NEWREF'))->assertSessionHasErrors('payment_reference');
        app(Payments::class)->review($this->admin, $p, 'rejected', 'المرجع غير مطابق');
        $this->patch(route('captain.payments.correct', $p), $this->receipt('bank', 'merchant', 'NEWREF'))->assertSessionHasNoErrors();
        $this->assertSame('pending', $p->fresh()->status);
        $this->assertSame(0, (int) $o->entry()->first()->collected_minor);
        $this->assertSame(-1000, (int) $o->entry()->first()->merchant_net_minor);
        $other = $this->staff('captain', '0591000003');
        $this->asStaff($other)->patch(route('captain.payments.correct', $p), $this->receipt())->assertForbidden();
    }

    public function test_cash_cannot_be_claimed_as_direct_merchant_transfer(): void
    {
        $o = $this->ready();
        $this->asStaff($this->captain)->patch(route('captain.orders.status', $o), ['status' => 'delivered', 'collected_amount' => '100', 'payment_method' => 'cash', 'payment_recipient' => 'merchant'])->assertSessionHasErrors('payment_recipient');
        $this->assertSame('out_for_delivery', $o->fresh()->status);
    }

    public function test_pending_digital_captain_funds_cannot_enter_remittance(): void
    {
        $o = $this->ready();
        app(OrderWorkflow::class)->transition($this->captain, $o, 'delivered', null, 10000, $this->receipt());
        $this->expectException(ValidationException::class);
        app(Finance::class)->requestRemittance($this->captain);
    }
}
