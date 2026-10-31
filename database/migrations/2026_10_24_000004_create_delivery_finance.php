<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remittances', function (Blueprint $t) {
            $t->id();
            $t->foreignId('captain_id')->constrained('staff')->restrictOnDelete();
            $t->unsignedBigInteger('amount_minor');
            $t->string('status', 12)->default('pending');
            $t->string('reference', 160)->nullable();
            $t->foreignId('reviewed_by')->nullable()->constrained('staff')->restrictOnDelete();
            $t->timestamp('reviewed_at')->nullable();
            $t->timestamps();
        });
        Schema::create('settlements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('store_id')->constrained()->restrictOnDelete();
            $t->bigInteger('amount_minor');
            $t->string('reference', 160);
            $t->foreignId('paid_by')->constrained('staff')->restrictOnDelete();
            $t->timestamps();
        });
        Schema::create('captain_payouts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('captain_id')->constrained('staff')->restrictOnDelete();
            $t->unsignedBigInteger('amount_minor');
            $t->string('reference', 160);
            $t->foreignId('paid_by')->constrained('staff')->restrictOnDelete();
            $t->timestamps();
        });
        Schema::create('financial_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->unique()->constrained()->restrictOnDelete();
            $t->foreignId('store_id')->constrained()->restrictOnDelete();
            $t->foreignId('captain_id')->constrained('staff')->restrictOnDelete();
            $t->string('outcome', 12);
            $t->unsignedInteger('collected_minor');
            $t->unsignedInteger('fee_minor');
            $t->unsignedInteger('captain_pay_minor');
            $t->bigInteger('merchant_net_minor');
            $t->foreignId('remittance_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('settlement_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('captain_payout_id')->nullable()->constrained()->restrictOnDelete();
            $t->timestamps();
            $t->index(['captain_id', 'remittance_id']);
            $t->index(['store_id', 'settlement_id']);
        });
        Schema::create('expenses', function (Blueprint $t) {
            $t->id();
            $t->date('spent_on');
            $t->string('category', 100);
            $t->string('description', 500);
            $t->unsignedInteger('amount_minor');
            $t->string('reference', 160);
            $t->foreignId('recorded_by')->constrained('staff')->restrictOnDelete();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['expenses', 'financial_entries', 'captain_payouts', 'settlements', 'remittances'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
