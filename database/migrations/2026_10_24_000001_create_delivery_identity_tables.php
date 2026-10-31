<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Never silently skip or overwrite a host application's existing identity schema.
        foreach (['staff', 'users', 'stores', 'captain_profiles', 'account_audit_logs'] as $table) {
            if (Schema::hasTable($table)) {
                throw new RuntimeException("Table {$table} already exists; map the existing schema before installation.");
            }
        }

        Schema::create('staff', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('name', 120);
            $table->string('phone', 20)->unique();
            $table->string('email', 254)->nullable();
            $table->string('password');
            $table->enum('status', ['active', 'suspended'])->default('active');
            $table->boolean('must_change_password')->default(true);
            $table->unsignedInteger('session_version')->default(1);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->index(['status', 'id']);
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('name', 120);
            $table->string('phone', 20)->unique();
            $table->string('email', 254)->nullable();
            $table->string('password');
            $table->enum('status', ['pending', 'active', 'suspended'])->default('pending');
            $table->boolean('must_change_password')->default(false);
            $table->unsignedInteger('session_version')->default(1);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->index(['status', 'id']);
        });

        Schema::create('stores', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('owner_user_id')->unique()->constrained('users')->restrictOnDelete();
            $table->string('name', 160);
            $table->enum('status', ['pending', 'active', 'suspended', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by_staff_id')->nullable()->constrained('staff')->restrictOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_reason', 500)->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at', 'id']);
        });

        Schema::create('captain_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('staff_id')->unique()->constrained('staff')->restrictOnDelete();
            $table->enum('vehicle_type', ['bicycle', 'motorcycle', 'car']);
            $table->timestamps();
        });

        Schema::create('account_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_staff_id')->nullable()->constrained('staff')->restrictOnDelete();
            $table->foreignId('subject_staff_id')->nullable()->constrained('staff')->restrictOnDelete();
            $table->foreignId('subject_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('subject_store_id')->nullable()->constrained('stores')->restrictOnDelete();
            $table->string('event', 80);
            $table->json('metadata');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['subject_staff_id', 'id']);
            $table->index(['subject_user_id', 'id']);
            $table->index(['subject_store_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_audit_logs');
        Schema::dropIfExists('captain_profiles');
        Schema::dropIfExists('stores');
        Schema::dropIfExists('users');
        Schema::dropIfExists('staff');
    }
};
