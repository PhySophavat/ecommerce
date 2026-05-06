<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('payment_reference', 120)->nullable()->after('payment_status');
            $table->text('payment_notes')->nullable()->after('notes');
            $table->timestamp('paid_at')->nullable()->after('placed_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn([
                'payment_reference',
                'payment_notes',
                'paid_at',
            ]);
        });
    }
};
