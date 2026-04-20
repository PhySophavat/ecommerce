<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FiftyProductsSeeder extends Seeder
{
    /**
     * Seed the application's database with 50 catalog products.
     */
    public function run(): void
    {
        $this->call(StoreDemoSeeder::class);

        $categories = Category::query()
            ->get()
            ->keyBy('slug');

        $catalog = [
            [
                'slug' => 'women-cloths',
                'type' => 'women',
                'base_price' => 44.00,
                'nouns' => [
                    'Blouse',
                    'Pleated Skirt',
                    'Linen Shirt',
                    'Wide Pant',
                    'Soft Blazer',
                    'Midi Dress',
                    'Light Cardigan',
                    'Cropped Jacket',
                    'Sleeveless Top',
                    'Flow Skirt',
                ],
            ],
            [
                'slug' => 'man-cloths',
                'type' => 'men',
                'base_price' => 49.00,
                'nouns' => [
                    'Oxford Shirt',
                    'Polo Tee',
                    'Chino Pant',
                    'Utility Jacket',
                    'Jogger Pant',
                    'Structured Hoodie',
                    'Cotton Blazer',
                    'Layered Tee',
                    'Casual Overshirt',
                    'Denim Shirt',
                ],
            ],
            [
                'slug' => 'kid-cloths',
                'type' => 'women',
                'base_price' => 28.00,
                'nouns' => [
                    'Play Set',
                    'Kids Tee',
                    'Mini Pant',
                    'School Shirt',
                    'Comfy Hoodie',
                    'Weekend Short',
                    'Cotton Dress',
                    'Active Jacket',
                    'Relaxed Jogger',
                    'Soft Cardigan',
                ],
            ],
            [
                'slug' => 'sweater',
                'type' => 'men',
                'base_price' => 52.00,
                'nouns' => [
                    'Wool Sweater',
                    'Rib Knit',
                    'Crew Neck',
                    'Zip Sweater',
                    'Heavy Knit',
                    'Mock Neck',
                    'Soft Pullover',
                    'Cable Knit',
                    'Studio Sweater',
                    'Winter Layer',
                ],
            ],
        ];

        $prefixes = ['Modern', 'Classic', 'Studio', 'Urban', 'Daily', 'Prime', 'Essential', 'Signature', 'Soft', 'Tailored'];
        $suffixes = ['Edit', 'Core', 'Line', 'Select', 'Series'];
        $statuses = ['active', 'draft', 'scheduled'];
        $themes = ['cobalt', 'forest', 'sand', 'graphite', 'midnight', 'sky', 'ink', 'plum', 'denim', 'lilac'];

        foreach (range(11, 50) as $number) {
            $catalogIndex = ($number - 11) % count($catalog);
            $localIndex = intdiv($number - 11, count($catalog));
            $config = $catalog[$catalogIndex];
            $category = $categories->get($config['slug']);

            if (! $category) {
                continue;
            }

            $prefix = $prefixes[$localIndex % count($prefixes)];
            $noun = $config['nouns'][$localIndex % count($config['nouns'])];
            $suffix = $suffixes[$number % count($suffixes)];
            $name = "{$prefix} {$noun} {$suffix}";
            $price = round($config['base_price'] + (($number % 7) * 4.35) + ($localIndex * 1.25), 2);
            $compareAt = $number % 3 === 0 ? null : round($price + 10 + (($number % 5) * 1.75), 2);
            $inventory = 18 + (($number * 7) % 91);
            $status = $statuses[$number % count($statuses)];

            $attributes = [
                'category_id' => $category->id,
                'name' => $name,
                'slug' => Str::slug($name).'-'.sprintf('%03d', $number),
                'tagline' => $this->taglineFor($noun),
                'description' => $this->descriptionFor($name, $noun),
                'price' => $price,
                'compare_at_price' => $compareAt,
                'inventory' => $inventory,
                'status' => $status,
                'is_featured' => $number % 4 === 0,
                'theme' => $themes[$number % count($themes)],
                'rating' => round(4.20 + (($number % 8) * 0.08), 2),
                'reviews_count' => 18 + (($number * 13) % 220),
            ];

            if (Schema::hasColumn('products', 'type')) {
                $attributes['type'] = $config['type'];
            }

            Product::query()->updateOrCreate(
                ['sku' => sprintf('SPD-AUTO-%03d', $number)],
                $attributes,
            );
        }
    }

    private function taglineFor(string $noun): string
    {
        return match (true) {
            str_contains(Str::lower($noun), 'jacket') => 'Layer-ready outerwear with a clean store presentation.',
            str_contains(Str::lower($noun), 'dress') => 'Easy styling piece with a polished visual finish.',
            str_contains(Str::lower($noun), 'pant'), str_contains(Str::lower($noun), 'jogger'), str_contains(Str::lower($noun), 'short') => 'Built for movement, comfort, and steady sell-through.',
            str_contains(Str::lower($noun), 'sweater'), str_contains(Str::lower($noun), 'knit'), str_contains(Str::lower($noun), 'pullover') => 'Soft texture and seasonal weight for premium layering.',
            default => 'A clean wardrobe staple designed for everyday catalog use.',
        };
    }

    private function descriptionFor(string $name, string $noun): string
    {
        return "The {$name} brings a focused merchandising look with dependable comfort. This {$noun} is designed for daily wear, clean styling, and a balanced price point for the storefront.";
    }
}
