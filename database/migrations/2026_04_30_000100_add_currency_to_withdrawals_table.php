<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('withdrawals', function (Blueprint $table): void {
            $table->string('currency', 3)->default('USD')->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('withdrawals', function (Blueprint $table): void {
            $table->dropColumn('currency');
        });
    }
};
