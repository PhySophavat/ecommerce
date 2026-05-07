<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_balances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('merchant_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('total_balance', 14, 2)->default(0);
            $table->decimal('available_balance', 14, 2)->default(0);
            $table->decimal('pending_balance', 14, 2)->default(0);
            $table->decimal('total_in', 14, 2)->default(0);
            $table->decimal('total_out', 14, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->string('payment_code')->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('payment_method', ['ABA', 'ACLEDA', 'Wing', 'Cash', 'Card']);
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['success', 'failed', 'pending', 'cancelled'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'merchant_id']);
            $table->index(['merchant_id', 'status']);
            $table->index(['payment_method', 'status']);
        });

        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->string('transaction_code')->unique();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['IN', 'OUT']);
            $table->decimal('amount', 14, 2);
            $table->enum('currency', ['USD', 'KHR'])->default('USD');
            $table->enum('method', ['ABA', 'ACLEDA', 'Wing', 'Cash', 'Card']);
            $table->enum('status', ['success', 'failed', 'pending', 'cancelled'])->default('pending');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['merchant_id', 'type', 'status']);
            $table->index(['order_id', 'type']);
        });

        Schema::create('withdraw_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('withdrawal_id')->unique()->constrained('withdrawals')->cascadeOnDelete();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('merchant_bank_account_id')->nullable()->constrained('merchant_bank_accounts')->nullOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['success', 'failed', 'pending', 'cancelled'])->default('pending');
            $table->text('note')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['merchant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdraw_requests');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('merchant_balances');
    }
};
