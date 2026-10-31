<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('captain_profiles', function (Blueprint $t) {
            $t->string('vehicle_type', 32)->change();
        });
        Schema::create('neighborhoods', function (Blueprint $t) {
            $t->id();
            $t->foreignId('zone_id')->constrained()->restrictOnDelete();
            $t->string('name', 120);
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->unique(['zone_id', 'name']);
        });
        Schema::table('pickup_addresses', function (Blueprint $t) {
            $t->foreignId('neighborhood_id')->nullable()->constrained()->restrictOnDelete();
        });
        Schema::table('tariffs', function (Blueprint $t) {
            $t->string('captain_pay_mode', 12)->default('fixed');
            $t->unsignedSmallInteger('captain_share_bps')->default(0);
        });
        Schema::table('orders', function (Blueprint $t) {
            $t->foreignId('to_neighborhood_id')->nullable()->constrained('neighborhoods')->restrictOnDelete();
            $t->string('delivery_mode', 12)->default('now');
            $t->timestamp('scheduled_for')->nullable()->index();
            $t->string('captain_pay_mode', 12)->default('fixed');
            $t->unsignedSmallInteger('captain_share_bps')->default(0);
        });
        Schema::create('order_payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->unique()->constrained()->restrictOnDelete();
            $t->string('method', 20);
            $t->string('recipient', 12);
            $t->unsignedInteger('amount_minor');
            $t->string('account_label', 160);
            $t->string('reference', 160);
            $t->string('status', 12)->default('pending');
            $t->foreignId('reported_by')->constrained('staff')->restrictOnDelete();
            $t->foreignId('reviewed_by')->nullable()->constrained('staff')->restrictOnDelete();
            $t->timestamp('reviewed_at')->nullable();
            $t->string('review_note', 500)->nullable();
            $t->timestamps();
            // One receipt reference per receiving account/provider; prevents duplicate claims.
            $t->unique(['method', 'account_label', 'reference'], 'payment_receipt_unique');
        });
        Schema::table('financial_entries', function (Blueprint $t) {
            $t->unsignedInteger('merchant_received_minor')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('financial_entries', fn (Blueprint $t) => $t->dropColumn('merchant_received_minor'));
        Schema::dropIfExists('order_payments');
        Schema::table('orders', function (Blueprint $t) {
            $t->dropConstrainedForeignId('to_neighborhood_id');
            $t->dropColumn(['delivery_mode', 'scheduled_for', 'captain_pay_mode', 'captain_share_bps']);
        });
        Schema::table('tariffs', fn (Blueprint $t) => $t->dropColumn(['captain_pay_mode', 'captain_share_bps']));
        Schema::table('pickup_addresses', fn (Blueprint $t) => $t->dropConstrainedForeignId('neighborhood_id'));
        Schema::dropIfExists('neighborhoods');
        // Keep vehicle_type as a string so existing electrical bicycle records remain readable.
    }
};
