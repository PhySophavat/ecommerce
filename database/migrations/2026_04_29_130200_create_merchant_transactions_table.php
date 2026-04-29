<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['sale', 'platform_fee', 'withdrawal']);
            $table->decimal('amount', 12, 2);
            $table->string('description');
            $table->timestamps();

            $table->index(['merchant_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_transactions');
    }
};
