<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table): void {
            $table->decimal('balance_total', 12, 2)->default(0)->after('approved_at');
            $table->decimal('available_balance', 12, 2)->default(0)->after('balance_total');
            $table->decimal('pending_balance', 12, 2)->default(0)->after('available_balance');
            $table->decimal('total_platform_fee_paid', 12, 2)->default(0)->after('pending_balance');
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table): void {
            $table->dropColumn([
                'balance_total',
                'available_balance',
                'pending_balance',
                'total_platform_fee_paid',
            ]);
        });
    }
};
