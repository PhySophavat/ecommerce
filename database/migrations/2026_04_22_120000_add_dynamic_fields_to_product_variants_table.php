<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table): void {
            $table->string('label')->nullable()->after('product_id');
            $table->json('option_values')->nullable()->after('color');
            $table->string('sku')->nullable()->unique()->after('option_values');
            $table->string('image_path')->nullable()->after('sku');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table): void {
            $table->dropUnique(['sku']);
            $table->dropColumn(['label', 'option_values', 'sku', 'image_path']);
        });
    }
};
