<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant_deposits', function (Blueprint $table): void {
            $table->string('bank_name')->nullable()->after('merchant_id');
            $table->string('account_name')->nullable()->after('bank_name');
            $table->string('account_number')->nullable()->after('account_name');
            $table->string('phone_number')->nullable()->after('account_number');
        });

        DB::table('merchant_deposits')
            ->whereNull('bank_name')
            ->update([
                'bank_name' => DB::raw("CASE WHEN payment_method = 'bank_transfer' THEN 'Bank Transfer' ELSE 'KHQR' END"),
            ]);
    }

    public function down(): void
    {
        Schema::table('merchant_deposits', function (Blueprint $table): void {
            $table->dropColumn([
                'bank_name',
                'account_name',
                'account_number',
                'phone_number',
            ]);
        });
    }
};
