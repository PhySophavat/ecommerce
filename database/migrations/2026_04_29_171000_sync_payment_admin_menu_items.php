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

        $payments = AdminMenu::query()->updateOrCreate(
            ['slug' => 'payments'],
            [
                'parent_id' => null,
                'label' => 'Payments',
                'icon' => 'payments',
                'path' => null,
                'sort_order' => 5,
                'is_enabled' => false,
            ],
        );

        AdminMenu::query()->updateOrCreate(
            ['slug' => 'deposits'],
            [
                'parent_id' => $payments->id,
                'label' => 'Deposits',
                'icon' => null,
                'path' => '/admin/deposits',
                'sort_order' => 3,
                'is_enabled' => true,
            ],
        );

        AdminMenu::query()->updateOrCreate(
            ['slug' => 'withdrawals'],
            [
                'parent_id' => $payments->id,
                'label' => 'Withdrawals',
                'icon' => null,
                'path' => '/admin/withdrawals',
                'sort_order' => 4,
                'is_enabled' => true,
            ],
        );
    }

    public function down(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('admin_menus')) {
            return;
        }

        AdminMenu::query()->whereIn('slug', ['deposits', 'withdrawals'])->delete();
    }
};
