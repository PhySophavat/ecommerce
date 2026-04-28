<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Modify existing status column to use enum
        Schema::table('products', function (Blueprint $table) {
            // First, update existing 'active' status to 'pending' for products without merchant_id
            // Products created by admin will remain active until changed
            // Products created by merchants will be pending
            
            // Add new columns
            $table->foreignId('approved_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->foreignId('merchant_id')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->text('admin_note')->nullable()->after('merchant_id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['merchant_id']);
            $table->dropColumn(['approved_by', 'approved_at', 'merchant_id', 'admin_note']);
        });
    }
};