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
                'slug' => 'beauty',
                'type' => 'women',
                'base_price' => 22.00,
                'nouns' => [
                    'Serum',
                    'Cleanser',
                    'Lip Tint',
                    'Face Cream',
                    'Body Mist',
                    'Toner',
                    'Glow Mask',
                    'Moisturizer',
                    'Shampoo',
                    'Makeup Kit',
                ],
            ],
            [
                'slug' => 'fashion',
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
                'slug' => 'sport',
                'type' => 'men',
                'base_price' => 34.00,
                'nouns' => [
                    'Training Tee',
                    'Jogger Pant',
                    'Running Short',
                    'Track Jacket',
                    'Gym Bag',
                    'Yoga Mat',
                    'Sports Bottle',
                    'Training Hoodie',
                    'Compression Top',
                    'Sport Cap',
                ],
            ],
            [
                'slug' => 'electronic',
                'type' => 'men',
                'base_price' => 68.00,
                'nouns' => [
                    'Earbuds',
                    'Smart Watch',
                    'Speaker',
                    'Power Bank',
                    'Keyboard',
                    'Mouse',
                    'Monitor Light',
                    'Charger',
                    'Tablet Stand',
                    'Webcam',
                ],
            ],
            [
                'slug' => 'home',
                'type' => 'women',
                'base_price' => 31.00,
                'nouns' => [
                    'Table Lamp',
                    'Throw Blanket',
                    'Candle Set',
                    'Storage Basket',
                    'Wall Clock',
                    'Cushion Cover',
                    'Ceramic Vase',
                    'Coffee Mug',
                    'Bed Sheet',
                    'Diffuser',
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
            str_contains(Str::lower($noun), 'serum'),
            str_contains(Str::lower($noun), 'cleanser'),
            str_contains(Str::lower($noun), 'cream'),
            str_contains(Str::lower($noun), 'mist'),
            str_contains(Str::lower($noun), 'toner'),
            str_contains(Str::lower($noun), 'mask'),
            str_contains(Str::lower($noun), 'shampoo'),
            str_contains(Str::lower($noun), 'makeup') => 'Beauty essential formulated for everyday use and polished shelf appeal.',
            str_contains(Str::lower($noun), 'jacket') => 'Layer-ready outerwear with a clean store presentation.',
            str_contains(Str::lower($noun), 'dress') => 'Easy styling piece with a polished visual finish.',
            str_contains(Str::lower($noun), 'pant'), str_contains(Str::lower($noun), 'jogger'), str_contains(Str::lower($noun), 'short') => 'Built for movement, comfort, and steady sell-through.',
            str_contains(Str::lower($noun), 'sweater'), str_contains(Str::lower($noun), 'knit'), str_contains(Str::lower($noun), 'pullover') => 'Soft texture and seasonal weight for premium layering.',
            str_contains(Str::lower($noun), 'earbuds'),
            str_contains(Str::lower($noun), 'watch'),
            str_contains(Str::lower($noun), 'speaker'),
            str_contains(Str::lower($noun), 'bank'),
            str_contains(Str::lower($noun), 'keyboard'),
            str_contains(Str::lower($noun), 'mouse'),
            str_contains(Str::lower($noun), 'light'),
            str_contains(Str::lower($noun), 'charger'),
            str_contains(Str::lower($noun), 'stand'),
            str_contains(Str::lower($noun), 'webcam') => 'Smart tech designed for daily convenience, focus, and dependable performance.',
            str_contains(Str::lower($noun), 'lamp'),
            str_contains(Str::lower($noun), 'blanket'),
            str_contains(Str::lower($noun), 'candle'),
            str_contains(Str::lower($noun), 'basket'),
            str_contains(Str::lower($noun), 'clock'),
            str_contains(Str::lower($noun), 'cover'),
            str_contains(Str::lower($noun), 'vase'),
            str_contains(Str::lower($noun), 'mug'),
            str_contains(Str::lower($noun), 'sheet'),
            str_contains(Str::lower($noun), 'diffuser') => 'Home essential made to add warmth, comfort, and clean visual presentation.',
            default => 'A clean wardrobe staple designed for everyday catalog use.',
        };
    }

    private function descriptionFor(string $name, string $noun): string
    {
        return "The {$name} is built for dependable everyday use with a clean merchandising profile. This {$noun} is positioned for strong value, easy browsing, and a polished storefront presentation.";
    }
}
