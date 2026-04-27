<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class StorefrontApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_payload_contains_category_navigation_products_and_slides(): void
    {
        $this->seed();

        $response = $this->getJson('/api/storefront');

        $response
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('meta.brand', 'Northstar Goods')
                ->where('meta.eyebrow', 'Category-led storefront')
                ->where('links.admin_sliders', url('/admin/sliders'))
                ->where('links.admin_products', url('/admin/products'))
                ->where('categories.0.slug', 'beauty')
                ->where('categories.0.products_count', 1)
                ->where('slides.0.category_slug', 'beauty')
                ->where('slides.0.title', 'Beauty')
                ->where('products.count', 8)
                ->where('products.featured.0.category_slug', 'beauty')
                ->has('categories', 5)
                ->has('slides', 2)
                ->has('products.items', 8)
                ->has('products.featured', 5)
                ->missing('users')
                ->missing('cart'));
    }
}
