<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zones', function (Blueprint $t) {
            $t->id();
            $t->string('name', 120)->unique();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('pickup_addresses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('store_id')->constrained()->restrictOnDelete();
            $t->foreignId('zone_id')->constrained()->restrictOnDelete();
            $t->string('label', 100);
            $t->string('address', 500);
            $t->string('contact_phone', 20);
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->index(['store_id', 'active']);
        });
        Schema::create('tariffs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('from_zone_id')->constrained('zones')->restrictOnDelete();
            $t->foreignId('to_zone_id')->constrained('zones')->restrictOnDelete();
            $t->unsignedInteger('fee_minor');
            $t->unsignedInteger('captain_pay_minor');
            $t->unsignedInteger('return_fee_minor')->default(0);
            $t->unsignedInteger('captain_return_pay_minor')->default(0);
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->unique(['from_zone_id', 'to_zone_id']);
        });
        Schema::create('store_agreements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('store_id')->unique()->constrained()->restrictOnDelete();
            $t->unsignedSmallInteger('discount_bps')->default(0);
            $t->date('starts_on');
            $t->date('ends_on')->nullable();
            $t->string('notes', 500)->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('coupons', function (Blueprint $t) {
            $t->id();
            $t->string('code', 40)->unique();
            $t->foreignId('store_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('type', 10);
            $t->unsignedInteger('value');
            $t->unsignedInteger('max_discount_minor')->nullable();
            $t->unsignedInteger('min_fee_minor')->default(0);
            $t->unsignedInteger('max_uses')->default(100);
            $t->unsignedInteger('per_store_limit')->default(1);
            $t->date('starts_on');
            $t->date('ends_on');
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('delivery_packages', function (Blueprint $t) {
            $t->id();
            $t->string('name', 120);
            $t->unsignedInteger('price_minor');
            $t->unsignedSmallInteger('duration_days');
            $t->unsignedInteger('included_deliveries')->default(0);
            $t->unsignedSmallInteger('discount_bps')->default(0);
            $t->string('benefits', 500)->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('subscriptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('store_id')->constrained()->restrictOnDelete();
            $t->foreignId('delivery_package_id')->constrained()->restrictOnDelete();
            $t->string('package_name', 120);
            $t->unsignedInteger('price_minor');
            $t->unsignedSmallInteger('duration_days');
            $t->unsignedInteger('included_deliveries');
            $t->unsignedInteger('remaining_deliveries')->default(0);
            $t->unsignedSmallInteger('discount_bps');
            $t->string('status', 12)->default('pending');
            $t->timestamp('starts_at')->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->foreignId('approved_by')->nullable()->constrained('staff')->restrictOnDelete();
            $t->string('payment_reference', 160)->nullable();
            $t->timestamps();
            $t->index(['store_id', 'status', 'expires_at']);
        });
        Schema::create('delivery_credits', function (Blueprint $t) {
            $t->id();
            $t->foreignId('store_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('quantity');
            $t->unsignedInteger('remaining');
            $t->date('expires_on');
            $t->string('reason', 500);
            $t->foreignId('granted_by')->constrained('staff')->restrictOnDelete();
            $t->timestamps();
        });
        Schema::create('delivery_audits', function (Blueprint $t) {
            $t->id();
            $t->foreignId('staff_id')->nullable()->constrained('staff')->restrictOnDelete();
            $t->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->string('event', 100);
            $t->string('subject', 100);
            $t->json('data');
            $t->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach (['delivery_audits', 'delivery_credits', 'subscriptions', 'delivery_packages', 'coupons', 'store_agreements', 'tariffs', 'pickup_addresses', 'zones'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
