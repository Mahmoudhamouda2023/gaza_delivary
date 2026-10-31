<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->ulid('public_id')->unique();
            $t->string('tracking_token', 64)->unique();
            $t->foreignId('store_id')->constrained()->restrictOnDelete();
            $t->uuid('idempotency_key');
            $t->foreignId('pickup_address_id')->constrained()->restrictOnDelete();
            $t->foreignId('to_zone_id')->constrained('zones')->restrictOnDelete();
            $t->foreignId('captain_id')->nullable()->constrained('staff')->restrictOnDelete();
            $t->string('status', 24)->default('created');
            $t->string('recipient_name', 120);
            $t->string('recipient_phone', 20);
            $t->string('recipient_address', 500);
            $t->string('pickup_snapshot', 700);
            $t->string('pickup_phone', 20);
            $t->string('destination_snapshot', 120);
            $t->string('description', 500);
            $t->string('merchant_reference', 100)->nullable();
            $t->string('notes', 500)->nullable();
            $t->string('fee_payer', 12);
            $t->unsignedInteger('goods_minor');
            $t->unsignedInteger('base_fee_minor');
            $t->unsignedInteger('discount_minor');
            $t->unsignedInteger('fee_minor');
            $t->unsignedInteger('captain_pay_minor');
            $t->unsignedInteger('return_fee_minor');
            $t->unsignedInteger('captain_return_pay_minor');
            $t->unsignedInteger('collect_minor');
            $t->string('discount_source', 100)->nullable();
            $t->foreignId('coupon_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('subscription_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('delivery_credit_id')->nullable()->constrained()->restrictOnDelete();
            $t->boolean('benefit_reserved')->default(false);
            $t->timestamp('delivered_at')->nullable();
            $t->timestamps();
            $t->unique(['store_id', 'idempotency_key']);
            $t->index(['store_id', 'status', 'id']);
            $t->index(['captain_id', 'status', 'id']);
            $t->index(['status', 'created_at']);
        });
        Schema::create('coupon_redemptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('coupon_id')->constrained()->restrictOnDelete();
            $t->foreignId('store_id')->constrained()->restrictOnDelete();
            $t->foreignId('order_id')->unique()->constrained()->restrictOnDelete();
            $t->timestamps();
            $t->index(['coupon_id', 'store_id']);
        });
        Schema::create('order_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->restrictOnDelete();
            $t->string('status', 24);
            $t->string('note', 500)->nullable();
            $t->foreignId('staff_id')->nullable()->constrained('staff')->restrictOnDelete();
            $t->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('created_at')->useCurrent();
        });
        Schema::create('order_issues', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->restrictOnDelete();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->string('message', 1000);
            $t->string('reply', 1000)->nullable();
            $t->foreignId('resolved_by')->nullable()->constrained('staff')->restrictOnDelete();
            $t->timestamp('resolved_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['order_issues', 'order_events', 'coupon_redemptions', 'orders'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
