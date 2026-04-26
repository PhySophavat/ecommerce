<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admin_menus')) {
            return;
        }

        if (
            DB::table('admin_menus')->where('slug', 'slider')->exists()
            && !DB::table('admin_menus')->where('slug', 'sliders')->exists()
        ) {
            DB::table('admin_menus')
                ->where('slug', 'slider')
                ->update(['slug' => 'sliders']);
        }

        DB::table('admin_menus')
            ->where('slug', 'featured-products')
            ->update([
                'path' => '/admin/products/featured',
                'is_enabled' => true,
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('admin_menus')) {
            return;
        }

        if (
            DB::table('admin_menus')->where('slug', 'sliders')->exists()
            && !DB::table('admin_menus')->where('slug', 'slider')->exists()
        ) {
            DB::table('admin_menus')
                ->where('slug', 'sliders')
                ->update(['slug' => 'slider']);
        }

        DB::table('admin_menus')
            ->where('slug', 'featured-products')
            ->update([
                'path' => null,
                'is_enabled' => false,
            ]);
    }
};
