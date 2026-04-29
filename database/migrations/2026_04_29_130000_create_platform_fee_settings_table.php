<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_fee_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('is_enabled')->default(true);
            $table->enum('fee_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('fee_value', 10, 2)->default(0);
            $table->enum('apply_stage', ['payment_success', 'order_completed'])->default('payment_success');
            $table->enum('deduct_from', ['merchant_balance'])->default('merchant_balance');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_fee_settings');
    }
};
