<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

            $table->index(['merchant_id', 'status']);
            $table->index(['merchant_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_bank_accounts');
    }
};
