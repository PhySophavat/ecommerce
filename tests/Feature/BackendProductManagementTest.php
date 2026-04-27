<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\StoreDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class BackendProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_frontend_and_admin_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('frontend.home'));
        $this->assertTrue(Route::has('admin.dashboard'));
        $this->assertTrue(Route::has('admin.products.index'));
        $this->assertTrue(Route::has('admin.products.create'));
        $this->assertTrue(Route::has('admin.products.featured'));
        $this->assertTrue(Route::has('admin.users.create'));
        $this->assertTrue(Route::has('admin.users.store'));
        $this->assertTrue(Route::has('api.admin.products.index'));
        $this->assertTrue(Route::has('api.admin.products.store'));
        $this->assertSame(url('/frontend'), route('frontend.home'));
        $this->assertSame(url('/admin/dashboard'), route('admin.dashboard'));
        $this->assertSame(url('/admin/products'), route('admin.products.index'));
        $this->assertSame(url('/admin/products/create'), route('admin.products.create'));
        $this->assertSame(url('/admin/products/featured'), route('admin.products.featured'));
        $this->assertSame(url('/admin/users/create'), route('admin.users.create'));
    }

    public function test_legacy_backend_routes_redirect_to_product_dashboard(): void
    {
        $this->get('/backend')->assertRedirect('/admin/products');
        $this->get('/backend/products')->assertRedirect('/admin/products');
        $this->get('/admin/users/create')->assertRedirect('/admin/products');
        $this->get('/admin/dashboard')->assertOk();
        $this->get('/admin/products/create')->assertOk();
        $this->assertSame(url('/admin/products'), route('admin.products.index'));
    }

    public function test_admin_product_dashboard_api_returns_menu_and_products(): void
    {
        $this->seed();

        $response = $this->getJson('/api/admin/products');

        $response
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('meta.brand', 'Spodut')
                ->where('meta.page_title', 'Products')
                ->where('menu.0.label', 'Dashboard')
                ->where('menu.1.label', 'Slides')
                ->where('menu.2.label', 'Products')
                ->where('menu.8.children.0.label', 'Admin users')
                ->where('products.count', 10)
                ->has('form.categories')
                ->has('summary', 4)
                ->has('highlights', 4)
                ->has('products.items', 10)
                ->etc());
    }

    public function test_admin_product_dashboard_api_can_return_dashboard_view_payload(): void
    {
        $this->seed();

        $response = $this->getJson('/api/admin/products?screen=dashboard');

        $response
            ->assertOk()
            ->assertJsonPath('screen', 'dashboard')
            ->assertJsonPath('meta.page_title', 'Dashboard')
            ->assertJsonPath('menu.0.is_active', true)
            ->assertJsonCount(0, 'menu.0.children')
            ->assertJsonPath('menu.1.is_active', false)
            ->assertJsonPath('menu.2.is_active', false);
    }

    public function test_admin_product_dashboard_api_can_return_add_product_view_payload(): void
    {
        $this->seed();

        $response = $this->getJson('/api/admin/products?screen=add-product');

        $response
            ->assertOk()
            ->assertJsonPath('screen', 'add-product')
            ->assertJsonPath('meta.page_title', 'Add Product')
            ->assertJsonPath('menu.2.is_active', true)
            ->assertJsonPath('menu.2.children.1.slug', 'add-product')
            ->assertJsonPath('menu.2.children.1.is_active', true);
    }

    public function test_admin_product_dashboard_api_can_return_featured_products_view_payload(): void
    {
        $this->seed();

        $response = $this->getJson('/api/admin/products?screen=featured-products');

        $response
            ->assertOk()
            ->assertJsonPath('screen', 'featured-products')
            ->assertJsonPath('meta.page_title', 'Featured Products')
            ->assertJsonPath('products.count', 5)
            ->assertJsonPath('products.items.0.is_featured', true);
    }

    public function test_admin_product_dashboard_api_falls_back_to_default_menu_when_no_menu_rows_exist(): void
    {
        $response = $this->getJson('/api/admin/products');

        $response
            ->assertOk()
            ->assertJsonPath('menu.0.label', 'Dashboard')
            ->assertJsonPath('menu.1.label', 'Slides')
            ->assertJsonPath('menu.2.label', 'Products')
            ->assertJsonCount(13, 'menu');
    }

    public function test_admin_product_dashboard_api_falls_back_to_default_menu_when_menu_table_is_missing(): void
    {
        Schema::dropIfExists('admin_menus');

        $response = $this->getJson('/api/admin/products');

        $response
            ->assertOk()
            ->assertJsonPath('menu.0.label', 'Dashboard')
            ->assertJsonPath('menu.1.label', 'Slides')
            ->assertJsonPath('menu.2.label', 'Products')
            ->assertJsonCount(13, 'menu');
    }

    public function test_admin_product_api_creates_a_product_with_images_and_variants(): void
    {
        Storage::fake('public');
        $this->seed(StoreDemoSeeder::class);

        $category = Category::query()->firstOrFail();

        $response = $this->post('/api/admin/products', [
            'name' => 'Field Product',
            'category_id' => $category->id,
            'type' => 'men',
            'description' => '<p>Editorial cotton layer for the new season.</p>',
            'price' => '120.00',
            'discount_price' => '96.50',
            'stock_quantity' => 24,
            'sku' => 'SPD-FIELD-011',
            'status' => 'active',
            'is_featured' => '1',
            'images' => [
                UploadedFile::fake()->image('front.jpg'),
                UploadedFile::fake()->image('back.jpg'),
            ],
            'variants' => [
                [
                    'label' => 'Black / M',
                    'attributes' => [
                        ['name' => 'Color', 'value' => 'Black'],
                        ['name' => 'Size', 'value' => 'M'],
                    ],
                    'variant_sku' => 'SPD-FIELD-011-BLK-M',
                    'price' => '96.50',
                    'stock' => 10,
                ],
                [
                    'label' => 'Red / L',
                    'attributes' => [
                        ['name' => 'Color', 'value' => 'Red'],
                        ['name' => 'Size', 'value' => 'L'],
                    ],
                    'variant_sku' => 'SPD-FIELD-011-RED-L',
                    'price' => '102.00',
                    'stock' => 14,
                ],
            ],
        ], [
            'Accept' => 'application/json',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Field Product was created successfully.');

        $product = Product::query()
            ->with(['images', 'variants'])
            ->where('sku', 'SPD-FIELD-011')
            ->firstOrFail();

        $this->assertSame('men', $product->type);
        $this->assertSame('96.50', $product->price);
        $this->assertSame('120.00', $product->compare_at_price);
        $this->assertSame(24, $product->inventory);
        $this->assertTrue($product->is_featured);
        $this->assertCount(2, $product->variants);
        $this->assertCount(2, $product->images);

        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'size' => 'M',
            'color' => 'Black',
            'price' => 96.50,
            'stock' => 10,
        ]);

        foreach ($product->images as $image) {
            Storage::disk('public')->assertExists($image->path);
        }
    }

    public function test_admin_product_api_can_generate_a_sku_for_the_simplified_vue_form(): void
    {
        Storage::fake('public');
        $this->seed(StoreDemoSeeder::class);

        $category = Category::query()->where('slug', 'fashion')->firstOrFail();

        $response = $this->post('/api/admin/products', [
            'name' => 'Vue Form Product',
            'category_id' => $category->id,
            'description' => 'Created from the simplified Vue admin form.',
            'price' => '88.00',
            'stock_quantity' => 9,
            'status' => 'active',
            'variants' => [
                [
                    'label' => 'M / Black / Cotton',
                    'attributes' => [
                        ['name' => 'Size', 'value' => 'M'],
                        ['name' => 'Color', 'value' => 'Black'],
                        ['name' => 'Material', 'value' => 'Cotton'],
                    ],
                    'price' => '88.00',
                    'stock' => 9,
                ],
            ],
        ], [
            'Accept' => 'application/json',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('product.name', 'Vue Form Product');

        $product = Product::query()->where('name', 'Vue Form Product')->firstOrFail();

        $this->assertNotEmpty($product->sku);
        $this->assertSame('SPD-VUE-FORM-PRODUCT', $product->sku);
        $this->assertNull($product->type);
    }

    public function test_backend_vue_request_creates_a_user(): void
    {
        $response = $this->postJson('/admin/users', [
            'name' => 'Jamie Carter',
            'email' => 'jamie@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user.name', 'Jamie Carter')
            ->assertJsonPath('user.email', 'jamie@example.com');

        $this->assertDatabaseHas('users', [
            'name' => 'Jamie Carter',
            'email' => 'jamie@example.com',
        ]);

        $user = User::query()->where('email', 'jamie@example.com')->firstOrFail();

        $this->assertNotSame('password123', $user->password);
    }
}
