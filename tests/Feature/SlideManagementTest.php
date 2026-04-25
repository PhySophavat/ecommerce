<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Slide;
use Database\Seeders\StoreDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SlideManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_slider_route_is_registered(): void
    {
        $this->assertTrue(Route::has('admin.sliders.index'));
        $this->assertSame(url('/admin/sliders'), route('admin.sliders.index'));

        $this->get('/admin/sliders')->assertOk();
    }

    public function test_admin_can_create_a_slide_with_image(): void
    {
        Storage::fake('public');
        $this->seed(StoreDemoSeeder::class);

        $category = Category::query()->where('slug', 'beauty')->firstOrFail();

        $response = $this->post('/api/admin/slides', [
            'category_id' => $category->id,
            'eyebrow' => 'New arrival',
            'title' => 'Beauty',
            'highlight' => 'Launch',
            'description' => 'Premium glow essentials built for the homepage hero.',
            'button_text' => 'Shop now',
            'button_url' => '/frontend',
            'badge_text' => '30% off',
            'is_active' => '1',
            'sort_order' => 3,
            'image' => UploadedFile::fake()->image('hero-slide.jpg', 1600, 720),
        ], [
            'Accept' => 'application/json',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('slide.category_id', (string) $category->id)
            ->assertJsonPath('slide.title', 'Beauty')
            ->assertJsonPath('slide.highlight', 'Launch');

        $slide = Slide::query()->where('title', 'Beauty')->where('highlight', 'Launch')->firstOrFail();

        $this->assertTrue($slide->is_active);
        $this->assertSame(3, $slide->sort_order);
        $this->assertNotNull($slide->image_path);

        Storage::disk('public')->assertExists($slide->image_path);
    }

    public function test_slide_dashboard_returns_next_sort_order_and_promoted_slides_menu_item(): void
    {
        $this->seed();

        $this->getJson('/api/admin/slides/dashboard')
            ->assertOk()
            ->assertJsonPath('meta.page_title', 'Slides')
            ->assertJsonPath('slides.next_sort_order', 3)
            ->assertJsonPath('menu.0.slug', 'dashboard')
            ->assertJsonPath('menu.1.slug', 'sliders')
            ->assertJsonPath('menu.1.label', 'Slides')
            ->assertJsonPath('menu.1.path', '/admin/sliders');
    }
}
