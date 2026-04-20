<?php

namespace Database\Seeders;

use App\Models\AdminMenu;
use App\Support\AdminMenuCatalog;
use Illuminate\Database\Seeder;

class AdminMenuSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach (AdminMenuCatalog::items() as $index => $item) {
            $parent = AdminMenu::query()->updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'label' => $item['label'],
                    'icon' => $item['icon'],
                    'path' => $item['path'],
                    'sort_order' => $index + 1,
                    'is_enabled' => $item['is_enabled'],
                    'parent_id' => null,
                ],
            );

            foreach ($item['children'] as $childIndex => $child) {
                AdminMenu::query()->updateOrCreate(
                    ['slug' => $child['slug']],
                    [
                        'label' => $child['label'],
                        'icon' => null,
                        'path' => $child['path'],
                        'sort_order' => $childIndex + 1,
                        'is_enabled' => $child['is_enabled'],
                        'parent_id' => $parent->id,
                    ],
                );
            }
        }
    }
}
