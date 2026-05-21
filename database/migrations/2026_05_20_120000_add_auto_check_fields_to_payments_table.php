<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            if (!Schema::hasColumn('payments', 'auto_check_status')) {
                $table->string('auto_check_status', 32)->default('pending')->after('verified_at');
            }

            if (!Schema::hasColumn('payments', 'auto_check_score')) {
                $table->unsignedTinyInteger('auto_check_score')->nullable()->after('auto_check_status');
            }

            if (!Schema::hasColumn('payments', 'auto_check_result')) {
                $table->json('auto_check_result')->nullable()->after('auto_check_score');
            }

            if (!Schema::hasColumn('payments', 'ocr_text')) {
                $table->longText('ocr_text')->nullable()->after('auto_check_result');
            }

            if (!Schema::hasColumn('payments', 'auto_checked_at')) {
                $table->timestamp('auto_checked_at')->nullable()->after('ocr_text');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            foreach (['auto_check_status', 'auto_check_score', 'auto_check_result', 'ocr_text', 'auto_checked_at'] as $column) {
                if (Schema::hasColumn('payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
