<?php
// database/migrations/2024_01_01_000001_create_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['client', 'freelancer', 'admin'])->default('client');
            $table->enum('status', ['active', 'suspended', 'pending'])->default('active');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->string('avatar_url')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->text('bio')->nullable();
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->string('country', 2)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->string('category');
            $table->json('skills_required')->nullable();
            $table->decimal('budget', 12, 2)->nullable();
            $table->enum('budget_type', ['fixed', 'hourly'])->default('fixed');
            $table->enum('status', ['pending', 'open', 'in_progress', 'closed', 'rejected'])->default('pending');
            $table->boolean('is_flagged')->default(false);
            $table->string('flag_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique();
            $table->foreignId('payer_id')->constrained('users');
            $table->foreignId('payee_id')->constrained('users');
            $table->foreignId('job_id')->nullable()->constrained('jobs');
            $table->decimal('amount', 12, 2);
            $table->decimal('platform_fee', 12, 2)->default(0);
            $table->char('currency', 3)->default('USD');
            $table->string('payment_method')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded', 'disputed', 'resolved'])->default('pending');
            $table->timestamp('refunded_at')->nullable();
            $table->string('refund_reason')->nullable();
            $table->timestamp('dispute_resolved_at')->nullable();
            $table->string('dispute_resolution')->nullable();
            $table->text('dispute_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('users');
    }
};
