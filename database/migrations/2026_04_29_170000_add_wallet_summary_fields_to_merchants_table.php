<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table): void {
            $table->decimal('total_withdrawn', 12, 2)->default(0)->after('pending_balance');
            $table->decimal('total_deposited', 12, 2)->default(0)->after('total_withdrawn');
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table): void {
            $table->dropColumn([
                'total_withdrawn',
                'total_deposited',
            ]);
        });
    }
};
