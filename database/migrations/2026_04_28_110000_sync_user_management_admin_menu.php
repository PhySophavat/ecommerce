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

        DB::table('admin_menus')->updateOrInsert(
            ['slug' => 'users-admin-management'],
            [
                'parent_id' => null,
                'label' => 'Users / Admin Management',
                'icon' => 'users',
                'path' => '/admin/users',
                'sort_order' => 8,
                'is_enabled' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );

        $parentId = DB::table('admin_menus')
            ->where('slug', 'users-admin-management')
            ->value('id');

        if (!$parentId) {
            return;
        }

        DB::table('admin_menus')->updateOrInsert(
            ['slug' => 'admin-users'],
            [
                'parent_id' => $parentId,
                'label' => 'Admin users',
                'icon' => null,
                'path' => '/admin/users',
                'sort_order' => 1,
                'is_enabled' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );

        DB::table('admin_menus')->updateOrInsert(
            ['slug' => 'merchants'],
            [
                'parent_id' => $parentId,
                'label' => 'Merchants',
                'icon' => null,
                'path' => '/admin/merchants',
                'sort_order' => 2,
                'is_enabled' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );

        DB::table('admin_menus')
            ->where('slug', 'roles-and-permissions')
            ->update([
                'parent_id' => $parentId,
                'sort_order' => 3,
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('admin_menus')) {
            return;
        }

        $now = now();

        DB::table('admin_menus')
            ->where('slug', 'users-admin-management')
            ->update([
                'path' => '/admin/products',
                'updated_at' => $now,
            ]);

        DB::table('admin_menus')
            ->where('slug', 'admin-users')
            ->update([
                'path' => '/admin/products',
                'sort_order' => 1,
                'updated_at' => $now,
            ]);

        DB::table('admin_menus')
            ->where('slug', 'roles-and-permissions')
            ->update([
                'sort_order' => 2,
                'updated_at' => $now,
            ]);

        DB::table('admin_menus')
            ->where('slug', 'merchants')
            ->delete();
    }
};
