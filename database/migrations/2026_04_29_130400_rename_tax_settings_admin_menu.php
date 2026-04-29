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

        $now = now();
        $settingsParentId = DB::table('admin_menus')->where('slug', 'settings')->value('id');

        if (
            DB::table('admin_menus')->where('slug', 'tax-settings')->exists()
            && !DB::table('admin_menus')->where('slug', 'platform-fee-settings')->exists()
        ) {
            DB::table('admin_menus')
                ->where('slug', 'tax-settings')
                ->update([
                    'slug' => 'platform-fee-settings',
                    'label' => 'Platform Fee Settings',
                    'path' => '/admin/settings/platform-fee',
                    'is_enabled' => true,
                    'updated_at' => $now,
                ]);

            return;
        }

        DB::table('admin_menus')->updateOrInsert(
            ['slug' => 'platform-fee-settings'],
            [
                'parent_id' => $settingsParentId,
                'label' => 'Platform Fee Settings',
                'icon' => null,
                'path' => '/admin/settings/platform-fee',
                'sort_order' => 4,
                'is_enabled' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('admin_menus')) {
            return;
        }

        $now = now();

        if (
            DB::table('admin_menus')->where('slug', 'platform-fee-settings')->exists()
            && !DB::table('admin_menus')->where('slug', 'tax-settings')->exists()
        ) {
            DB::table('admin_menus')
                ->where('slug', 'platform-fee-settings')
                ->update([
                    'slug' => 'tax-settings',
                    'label' => 'Tax settings',
                    'path' => null,
                    'is_enabled' => false,
                    'updated_at' => $now,
                ]);
        }
    }
};
