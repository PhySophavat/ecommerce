<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::rename('withdrawals', 'withdrawals_legacy');
        Schema::rename('merchant_bank_accounts', 'merchant_bank_accounts_legacy');

        Schema::create('merchant_bank_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete()->name('merchant_bank_accounts_v2_merchant_id_foreign');
            $table->string('bank_name');
            $table->string('account_holder_name');
            $table->string('account_number');
            $table->string('phone_number')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->enum('account_type', ['bank_account', 'khqr'])->default('bank_account');
            $table->string('qr_image_path')->nullable();
            $table->text('khqr_code')->nullable();
            $table->boolean('is_default')->default(false);
            $table->enum('status', ['pending', 'approved', 'rejected', 'disabled'])->default('pending');
            $table->text('reject_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->timestamps();

            $table->index(['merchant_id', 'status'], 'merchant_bank_accounts_v2_merchant_status_index');
            $table->index(['merchant_id', 'is_default'], 'merchant_bank_accounts_v2_merchant_default_index');
            $table->index(['merchant_id', 'currency'], 'merchant_bank_accounts_v2_merchant_currency_index');
        });

        DB::table('merchant_bank_accounts_legacy')->orderBy('id')->get()->each(function ($row): void {
            DB::table('merchant_bank_accounts')->insert([
                'id' => $row->id,
                'merchant_id' => $row->merchant_id,
                'bank_name' => $row->bank_name,
                'account_holder_name' => $row->account_name,
                'account_number' => $row->account_number,
                'phone_number' => null,
                'currency' => 'USD',
                'account_type' => $row->account_type === 'ewallet' ? 'khqr' : 'bank_account',
                'qr_image_path' => null,
                'khqr_code' => null,
                'is_default' => (bool) $row->is_default,
                'status' => $row->status === 'active' ? 'approved' : 'disabled',
                'reject_reason' => null,
                'approved_at' => $row->status === 'active' ? $row->created_at : null,
                'rejected_at' => null,
                'disabled_at' => $row->status === 'inactive' ? $row->updated_at : null,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        });

        Schema::create('withdrawals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete()->name('withdrawals_v2_merchant_id_foreign');
            $table->foreignId('bank_account_id')->constrained('merchant_bank_accounts')->restrictOnDelete()->name('withdrawals_v2_bank_account_id_foreign');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->decimal('fee_amount', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2);
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])->default('pending');
            $table->text('note')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['merchant_id', 'status'], 'withdrawals_v2_merchant_status_index');
            $table->index(['status', 'created_at'], 'withdrawals_v2_status_created_index');
        });

        DB::table('withdrawals_legacy')->orderBy('id')->get()->each(function ($row): void {
            DB::table('withdrawals')->insert([
                'id' => $row->id,
                'merchant_id' => $row->merchant_id,
                'bank_account_id' => $row->bank_account_id,
                'amount' => $row->amount,
                'currency' => $row->currency ?? 'USD',
                'fee_amount' => $row->fee_amount,
                'net_amount' => $row->net_amount,
                'status' => $row->status,
                'note' => $row->note,
                'approved_at' => $row->approved_at,
                'rejected_at' => $row->rejected_at,
                'paid_at' => $row->paid_at,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        });

        Schema::drop('withdrawals_legacy');
        Schema::drop('merchant_bank_accounts_legacy');
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::rename('withdrawals', 'withdrawals_v2');
        Schema::rename('merchant_bank_accounts', 'merchant_bank_accounts_v2');

        Schema::create('merchant_bank_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->string('bank_name');
            $table->string('account_name');
            $table->string('account_number');
            $table->enum('account_type', ['bank', 'ewallet'])->default('bank');
            $table->boolean('is_default')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index(['merchant_id', 'status'], 'merchant_bank_accounts_v1_merchant_status_index');
            $table->index(['merchant_id', 'is_default'], 'merchant_bank_accounts_v1_merchant_default_index');
        });

        DB::table('merchant_bank_accounts_v2')->orderBy('id')->get()->each(function ($row): void {
            DB::table('merchant_bank_accounts')->insert([
                'id' => $row->id,
                'merchant_id' => $row->merchant_id,
                'bank_name' => $row->bank_name,
                'account_name' => $row->account_holder_name,
                'account_number' => $row->account_number,
                'account_type' => $row->account_type === 'khqr' ? 'ewallet' : 'bank',
                'is_default' => (bool) $row->is_default,
                'status' => $row->status === 'approved' ? 'active' : 'inactive',
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        });

        Schema::create('withdrawals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained('merchant_bank_accounts')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->decimal('fee_amount', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2);
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])->default('pending');
            $table->text('note')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->string('currency', 3)->default('USD');

            $table->index(['merchant_id', 'status'], 'withdrawals_v1_merchant_status_index');
            $table->index(['status', 'created_at'], 'withdrawals_v1_status_created_index');
        });

        DB::table('withdrawals_v2')->orderBy('id')->get()->each(function ($row): void {
            DB::table('withdrawals')->insert([
                'id' => $row->id,
                'merchant_id' => $row->merchant_id,
                'bank_account_id' => $row->bank_account_id,
                'amount' => $row->amount,
                'fee_amount' => $row->fee_amount,
                'net_amount' => $row->net_amount,
                'status' => $row->status,
                'note' => $row->note,
                'approved_at' => $row->approved_at,
                'rejected_at' => $row->rejected_at,
                'paid_at' => $row->paid_at,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
                'currency' => $row->currency ?? 'USD',
            ]);
        });

        Schema::drop('withdrawals_v2');
        Schema::drop('merchant_bank_accounts_v2');
        Schema::enableForeignKeyConstraints();
    }
};
