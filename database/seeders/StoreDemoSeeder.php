<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Product;
use App\Models\Slide;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class StoreDemoSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $categories = $this->syncCategories();
        $defaultMerchantUser = $this->resolveStorefrontMerchantUser();

        collect([
            [
                'category_slug' => 'beauty',
                'name' => 'Hydrating Serum',
                'slug' => 'hydrating-serum',
                'sku' => 'SPD-TS-001',
                'tagline' => 'Lightweight hydration boost for a daily glow routine.',
                'description' => 'A fast-absorbing serum designed to keep skin refreshed, smooth, and camera ready across the day.',
                'price' => 39.80,
                'compare_at_price' => 49.00,
                'inventory' => 79,
                'status' => 'approved',
                'is_featured' => true,
                'theme' => 'cobalt',
                'rating' => 4.90,
                'reviews_count' => 124,
            ],
            [
                'category_slug' => 'fashion',
                'name' => 'Tailored Linen Shirt',
                'slug' => 'tailored-linen-shirt',
                'sku' => 'SPD-SH-002',
                'tagline' => 'Breathable staple with a clean smart-casual shape.',
                'description' => 'A lightweight shirt built for polished everyday styling, easy layering, and steady storefront appeal.',
                'price' => 76.89,
                'compare_at_price' => 84.00,
                'inventory' => 86,
                'status' => 'approved',
                'is_featured' => true,
                'theme' => 'forest',
                'rating' => 4.76,
                'reviews_count' => 93,
            ],
            [
                'category_slug' => 'sport',
                'name' => 'Flex Training Jogger',
                'slug' => 'flex-training-jogger',
                'sku' => 'SPD-PT-003',
                'tagline' => 'Stretch-built jogger made for training and recovery.',
                'description' => 'Designed for movement with a streamlined fit, breathable feel, and durable finish for repeat sessions.',
                'price' => 56.65,
                'compare_at_price' => null,
                'inventory' => 74,
                'status' => 'draft',
                'is_featured' => false,
                'theme' => 'sand',
                'rating' => 4.63,
                'reviews_count' => 58,
            ],
            [
                'category_slug' => 'home',
                'name' => 'Ceramic Table Lamp',
                'slug' => 'ceramic-table-lamp',
                'sku' => 'SPD-SW-004',
                'tagline' => 'Warm ambient lighting with a soft matte ceramic base.',
                'description' => 'A decorative lamp that brings gentle evening light and a refined accent to bedroom and living room spaces.',
                'price' => 66.07,
                'compare_at_price' => 78.00,
                'inventory' => 69,
                'status' => 'approved',
                'is_featured' => false,
                'theme' => 'graphite',
                'rating' => 4.72,
                'reviews_count' => 81,
            ],
            [
                'category_slug' => 'electronic',
                'name' => 'Wireless Earbuds',
                'slug' => 'wireless-earbuds',
                'sku' => 'SPD-SW-005',
                'tagline' => 'Compact audio pair with clear sound and stable connection.',
                'description' => 'A portable everyday audio essential with balanced sound, quick pairing, and a pocket-friendly charging case.',
                'price' => 86.07,
                'compare_at_price' => null,
                'inventory' => 69,
                'status' => 'approved',
                'is_featured' => true,
                'theme' => 'midnight',
                'rating' => 4.87,
                'reviews_count' => 104,
            ],
            [
                'category_slug' => 'beauty',
                'name' => 'Daily Glow Cleanser',
                'slug' => 'daily-glow-cleanser',
                'sku' => 'SPD-LJ-006',
                'tagline' => 'Gentle gel cleanser that lifts oil without stripping skin.',
                'description' => 'A balanced daily cleanser formulated to rinse clean, refresh the complexion, and support a simple skincare routine.',
                'price' => 36.00,
                'compare_at_price' => 44.00,
                'inventory' => 65,
                'status' => 'draft',
                'is_featured' => false,
                'theme' => 'sky',
                'rating' => 4.51,
                'reviews_count' => 45,
            ],
            [
                'category_slug' => 'sport',
                'name' => 'Performance Hoodie',
                'slug' => 'performance-hoodie',
                'sku' => 'SPD-HS-007',
                'tagline' => 'Training-ready layer with soft structure and easy warmth.',
                'description' => 'A versatile active hoodie designed for warm-ups, cooldowns, and off-duty comfort between sessions.',
                'price' => 46.78,
                'compare_at_price' => 58.00,
                'inventory' => 58,
                'status' => 'approved',
                'is_featured' => true,
                'theme' => 'ink',
                'rating' => 4.78,
                'reviews_count' => 89,
            ],
            [
                'category_slug' => 'electronic',
                'name' => 'Smart Desk Speaker',
                'slug' => 'smart-desk-speaker',
                'sku' => 'SPD-HS-008',
                'tagline' => 'Compact desktop speaker tuned for clear voice and music.',
                'description' => 'A minimal speaker made for workstations, delivering room-filling sound without taking over the desk.',
                'price' => 64.78,
                'compare_at_price' => null,
                'inventory' => 58,
                'status' => 'approved',
                'is_featured' => false,
                'theme' => 'plum',
                'rating' => 4.66,
                'reviews_count' => 67,
            ],
            [
                'category_slug' => 'home',
                'name' => 'Scented Candle Set',
                'slug' => 'scented-candle-set',
                'sku' => 'SPD-HS-009',
                'tagline' => 'Three-piece candle set for a calmer evening atmosphere.',
                'description' => 'A layered home fragrance set designed to add warmth, soft light, and a polished shelf presence.',
                'price' => 42.78,
                'compare_at_price' => 60.00,
                'inventory' => 58,
                'status' => 'approved',
                'is_featured' => true,
                'theme' => 'denim',
                'rating' => 4.83,
                'reviews_count' => 73,
            ],
            [
                'category_slug' => 'fashion',
                'name' => 'Structured Tote Bag',
                'slug' => 'structured-tote-bag',
                'sku' => 'SPD-HS-010',
                'tagline' => 'Clean everyday carry bag with structured shape and space.',
                'description' => 'A versatile tote built for daily errands, office carry, and simple premium presentation in the catalog.',
                'price' => 46.78,
                'compare_at_price' => null,
                'inventory' => 58,
                'status' => 'approved',
                'is_featured' => false,
                'theme' => 'lilac',
                'rating' => 4.58,
                'reviews_count' => 38,
            ],
        ])->each(function (array $product) use ($categories): void {
            Product::query()->updateOrCreate(
                ['sku' => $product['sku']],
                [
                    'category_id' => $categories[$product['category_slug']]->id,
                    'name' => $product['name'],
                    'slug' => $product['slug'],
                    'tagline' => $product['tagline'],
                    'description' => $product['description'],
                    'price' => $product['price'],
                    'compare_at_price' => $product['compare_at_price'],
                    'inventory' => $product['inventory'],
                    'status' => $product['status'],
                    'merchant_id' => $defaultMerchantUser->id,
                    'is_featured' => $product['is_featured'],
                    'theme' => $product['theme'],
                    'rating' => $product['rating'],
                    'reviews_count' => $product['reviews_count'],
                ],
            );
        });

        collect([
            [
                'category_slug' => 'beauty',
                'eyebrow' => 'New arrival',
                'title' => 'Beauty',
                'highlight' => 'Essentials',
                'description' => 'Premium skincare and makeup for natural glow.',
                'button_text' => 'Shop now',
                'button_url' => '/frontend',
                'badge_text' => 'Up to 30% off',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'category_slug' => 'fashion',
                'eyebrow' => 'Fresh edit',
                'title' => 'Modern',
                'highlight' => 'Layers',
                'description' => 'Clean silhouettes and elevated essentials for everyday wear.',
                'button_text' => 'Discover',
                'button_url' => '/frontend',
                'badge_text' => 'Weekly drop',
                'sort_order' => 2,
                'is_active' => true,
            ],
        ])->each(function (array $slide) use ($categories): void {
            Slide::query()->updateOrCreate(
                [
                    'title' => $slide['title'],
                    'highlight' => $slide['highlight'],
                ],
                [
                    'category_id' => $categories[$slide['category_slug']]->id ?? null,
                    'eyebrow' => $slide['eyebrow'],
                    'description' => $slide['description'],
                    'button_text' => $slide['button_text'],
                    'button_url' => $slide['button_url'],
                    'badge_text' => $slide['badge_text'],
                    'sort_order' => $slide['sort_order'],
                    'is_active' => $slide['is_active'],
                ],
            );
        });

        collect([
            [
                'number' => 'NS-1001',
                'status' => 'delivered',
                'customer_name' => 'Jamie Carter',
                'email' => 'jamie@example.com',
                'phone' => '+1-202-555-0101',
                'address_line1' => '15 Mercer Street',
                'address_line2' => null,
                'city' => 'New York',
                'postal_code' => '10012',
                'notes' => 'Leave at front desk.',
                'subtotal_amount' => 159.60,
                'shipping_amount' => 12.00,
                'total_amount' => 171.60,
                'placed_at' => now()->subDays(8),
            ],
            [
                'number' => 'NS-1002',
                'status' => 'processing',
                'customer_name' => 'Morgan Lee',
                'email' => 'morgan@example.com',
                'phone' => '+1-202-555-0122',
                'address_line1' => '245 Sunset Avenue',
                'address_line2' => 'Suite 8',
                'city' => 'Los Angeles',
                'postal_code' => '90028',
                'notes' => null,
                'subtotal_amount' => 122.85,
                'shipping_amount' => 10.00,
                'total_amount' => 132.85,
                'placed_at' => now()->subDays(5),
            ],
            [
                'number' => 'NS-1003',
                'status' => 'pending',
                'customer_name' => 'Avery Kim',
                'email' => 'avery@example.com',
                'phone' => '+1-202-555-0143',
                'address_line1' => '88 Harbor Road',
                'address_line2' => null,
                'city' => 'Seattle',
                'postal_code' => '98101',
                'notes' => 'Call on arrival.',
                'subtotal_amount' => 92.78,
                'shipping_amount' => 8.50,
                'total_amount' => 101.28,
                'placed_at' => now()->subDays(2),
            ],
            [
                'number' => 'NS-1004',
                'status' => 'shipped',
                'customer_name' => 'Riley Brooks',
                'email' => 'riley@example.com',
                'phone' => '+1-202-555-0174',
                'address_line1' => '402 Lake Drive',
                'address_line2' => null,
                'city' => 'Chicago',
                'postal_code' => '60611',
                'notes' => null,
                'subtotal_amount' => 181.20,
                'shipping_amount' => 14.00,
                'total_amount' => 195.20,
                'placed_at' => now()->subDay(),
            ],
        ])->each(function (array $order): void {
            Order::query()->updateOrCreate(
                ['number' => $order['number']],
                $order,
            );
        });
    }

    /**
     * @return Collection<string, Category>
     */
    private function syncCategories(): Collection
    {
        $definitions = collect([
            [
                'name' => 'Beauty',
                'slug' => 'beauty',
                'description' => 'Skincare, makeup, and self-care essentials for daily routines.',
                'accent' => '#ec4899',
                'legacy_slugs' => ['women-cloths'],
            ],
            [
                'name' => 'Fashion',
                'slug' => 'fashion',
                'description' => 'Apparel and accessories shaped for modern everyday styling.',
                'accent' => '#3457ff',
                'legacy_slugs' => ['man-cloths'],
            ],
            [
                'name' => 'Sport',
                'slug' => 'sport',
                'description' => 'Performance gear and active essentials built for movement.',
                'accent' => '#0f766e',
                'legacy_slugs' => ['kid-cloths'],
            ],
            [
                'name' => 'Electronic',
                'slug' => 'electronic',
                'description' => 'Smart devices and desktop tech for work, focus, and entertainment.',
                'accent' => '#f97316',
                'legacy_slugs' => [],
            ],
            [
                'name' => 'Home',
                'slug' => 'home',
                'description' => 'Decor and living essentials that add comfort and atmosphere.',
                'accent' => '#7c3aed',
                'legacy_slugs' => ['sweater'],
            ],
        ]);

        $categories = $definitions->mapWithKeys(function (array $definition): array {
            $model = Category::query()->where('slug', $definition['slug'])->first();

            if (! $model && $definition['legacy_slugs'] !== []) {
                $model = Category::query()->whereIn('slug', $definition['legacy_slugs'])->first();
            }

            $model ??= new Category();
            $model->fill([
                'name' => $definition['name'],
                'slug' => $definition['slug'],
                'description' => $definition['description'],
                'accent' => $definition['accent'],
            ]);
            $model->save();

            return [$definition['slug'] => $model];
        });

        $legacyMap = $definitions
            ->flatMap(fn (array $definition): array => collect($definition['legacy_slugs'])
                ->mapWithKeys(fn (string $legacySlug): array => [$legacySlug => $definition['slug']])
                ->all());

        foreach ($legacyMap as $legacySlug => $canonicalSlug) {
            $legacyCategory = Category::query()->where('slug', $legacySlug)->first();
            $canonicalCategory = $categories[$canonicalSlug] ?? null;

            if (! $legacyCategory || ! $canonicalCategory || $legacyCategory->is($canonicalCategory)) {
                continue;
            }

            Product::query()
                ->where('category_id', $legacyCategory->id)
                ->update(['category_id' => $canonicalCategory->id]);

            $legacyCategory->delete();
        }

        return $categories;
    }

    private function resolveStorefrontMerchantUser(): User
    {
        $approvedMerchantUser = User::query()
            ->where('role', 'merchant')
            ->where('email', '!=', 'merchant.shop@example.com')
            ->whereHas('merchant', fn ($query) => $query->where('status', 'Approved'))
            ->orderBy('id')
            ->first();

        return $approvedMerchantUser ?? $this->ensureDefaultMerchantUser();
    }

    private function ensureDefaultMerchantUser(): User
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'merchant.shop@example.com'],
            [
                'name' => 'Merchant Shop',
                'phone' => '089999999',
                'password' => Hash::make('password'),
                'role' => 'merchant',
            ],
        );

        Merchant::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'shop_name' => 'Merchant Shop',
                'business_type' => 'Fashion',
                'business_description' => 'Default storefront merchant for demo products.',
                'status' => 'Approved',
                'verification_status' => 'Verified',
            ],
        );

        return $user;
    }
}
