<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $categories = collect([
            [
                'name' => 'Workspace',
                'description' => 'Desk-first gear for focused sessions and softer, quieter workdays.',
                'accent' => '#f97316',
            ],
            [
                'name' => 'Carry',
                'description' => 'Travel-ready pieces that keep daily movement light and organized.',
                'accent' => '#14b8a6',
            ],
            [
                'name' => 'Rituals',
                'description' => 'Small tactile upgrades for coffee, audio, and end-of-day reset moments.',
                'accent' => '#84cc16',
            ],
        ])->mapWithKeys(function (array $attributes): array {
            $slug = Str::slug($attributes['name']);

            $category = Category::query()->updateOrCreate(
                ['slug' => $slug],
                $attributes + ['slug' => $slug],
            );

            return [$slug => $category];
        });

        collect([
            [
                'category' => 'workspace',
                'name' => 'Meridian Desk Lamp',
                'sku' => 'NSG-LAMP-001',
                'tagline' => 'Warm light with a low-profile silhouette.',
                'description' => 'A matte metal task lamp tuned for evening focus, with a slim base that stays out of the way.',
                'price' => 129.00,
                'compare_at_price' => 159.00,
                'inventory' => 14,
                'is_featured' => true,
                'theme' => 'ember',
                'rating' => 4.9,
                'reviews_count' => 86,
            ],
            [
                'category' => 'workspace',
                'name' => 'Quarry Stone Tray',
                'sku' => 'NSG-TRAY-004',
                'tagline' => 'A heavy catch-all for pens, keys, and cables.',
                'description' => 'Solid cast composite with soft edges, built to quiet the clutter around a laptop or entry shelf.',
                'price' => 42.00,
                'compare_at_price' => 55.00,
                'inventory' => 26,
                'is_featured' => false,
                'theme' => 'dune',
                'rating' => 4.7,
                'reviews_count' => 51,
            ],
            [
                'category' => 'workspace',
                'name' => 'Horizon Speaker',
                'sku' => 'NSG-AUDIO-011',
                'tagline' => 'Small-room audio with a grounded low end.',
                'description' => 'A compact wireless speaker that keeps detail intact at low volume for long working blocks.',
                'price' => 168.00,
                'compare_at_price' => 198.00,
                'inventory' => 8,
                'is_featured' => true,
                'theme' => 'midnight',
                'rating' => 4.8,
                'reviews_count' => 64,
            ],
            [
                'category' => 'carry',
                'name' => 'Atlas Carry Sleeve',
                'sku' => 'NSG-CARRY-002',
                'tagline' => 'Padded protection with a fast-access outer slip.',
                'description' => 'Built for 14-inch laptops, notebooks, and chargers without the bulk of a full backpack.',
                'price' => 48.00,
                'compare_at_price' => 64.00,
                'inventory' => 31,
                'is_featured' => false,
                'theme' => 'lagoon',
                'rating' => 4.6,
                'reviews_count' => 94,
            ],
            [
                'category' => 'carry',
                'name' => 'Northwind Tote',
                'sku' => 'NSG-TOTE-006',
                'tagline' => 'Structured canvas for market runs and office days.',
                'description' => 'A reinforced daily tote with deep pockets and a flat base that stands on its own.',
                'price' => 56.00,
                'compare_at_price' => 72.00,
                'inventory' => 22,
                'is_featured' => true,
                'theme' => 'lagoon',
                'rating' => 4.8,
                'reviews_count' => 73,
            ],
            [
                'category' => 'carry',
                'name' => 'Coastline Bottle',
                'sku' => 'NSG-BOTTLE-008',
                'tagline' => 'Thermal steel with a powder-soft finish.',
                'description' => 'Keeps coffee hot for commutes and cold brew crisp through long afternoons.',
                'price' => 34.00,
                'compare_at_price' => 44.00,
                'inventory' => 40,
                'is_featured' => false,
                'theme' => 'lagoon',
                'rating' => 4.5,
                'reviews_count' => 119,
            ],
            [
                'category' => 'rituals',
                'name' => 'Lumen Brew Set',
                'sku' => 'NSG-BREW-003',
                'tagline' => 'A ceramic pour-over pair for slow mornings.',
                'description' => 'Includes a dripper and matching server sized for two careful cups before the day picks up.',
                'price' => 74.00,
                'compare_at_price' => 92.00,
                'inventory' => 19,
                'is_featured' => false,
                'theme' => 'ember',
                'rating' => 4.9,
                'reviews_count' => 47,
            ],
            [
                'category' => 'rituals',
                'name' => 'Field Notes Set',
                'sku' => 'NSG-NOTES-007',
                'tagline' => 'Three linen notebooks for quick capture.',
                'description' => 'Dot grid interiors, thread-sewn binding, and enough structure for sketches or production lists.',
                'price' => 28.00,
                'compare_at_price' => 36.00,
                'inventory' => 65,
                'is_featured' => false,
                'theme' => 'moss',
                'rating' => 4.7,
                'reviews_count' => 132,
            ],
            [
                'category' => 'rituals',
                'name' => 'Ember Incense Stand',
                'sku' => 'NSG-RITUAL-010',
                'tagline' => 'A compact bronze rest with a hidden ash lip.',
                'description' => 'Designed to keep smoke low and cleanup easy for end-of-day reset routines.',
                'price' => 24.00,
                'compare_at_price' => 32.00,
                'inventory' => 28,
                'is_featured' => false,
                'theme' => 'ember',
                'rating' => 4.6,
                'reviews_count' => 58,
            ],
        ])->each(function (array $product) use ($categories): void {
            Product::query()->updateOrCreate(
                ['sku' => $product['sku']],
                [
                    'category_id' => $categories[$product['category']]->id,
                    'name' => $product['name'],
                    'slug' => Str::slug($product['name']),
                    'sku' => $product['sku'],
                    'tagline' => $product['tagline'],
                    'description' => $product['description'],
                    'price' => $product['price'],
                    'compare_at_price' => $product['compare_at_price'],
                    'inventory' => $product['inventory'],
                    'is_featured' => $product['is_featured'],
                    'theme' => $product['theme'],
                    'rating' => $product['rating'],
                    'reviews_count' => $product['reviews_count'],
                ],
            );
        });
    }
}
