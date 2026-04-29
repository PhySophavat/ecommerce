<?php

use App\Models\AdminMenu;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('admin_menus')) {
            return;
        }

        AdminMenu::query()->updateOrCreate(
            ['slug' => 'wallet'],
            [
                'parent_id' => null,
                'label' => 'Wallet',
                'icon' => 'wallet',
                'path' => '/admin/wallet',
                'sort_order' => 5,
                'is_enabled' => true,
            ],
        );
    }

    public function down(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('admin_menus')) {
            return;
        }

        AdminMenu::query()->where('slug', 'wallet')->delete();
    }
};
