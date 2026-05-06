<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('customer_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->string('payment_method', 32)->default('cash')->after('status');
            $table->string('payment_status', 24)->default('unpaid')->after('payment_method');
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->foreignId('merchant_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
            $table->string('product_image')->nullable()->after('product_name');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropForeign(['merchant_id']);
            $table->dropColumn([
                'merchant_id',
                'product_image',
            ]);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign(['customer_id']);
            $table->dropColumn([
                'customer_id',
                'payment_method',
                'payment_status',
            ]);
        });
    }
};
