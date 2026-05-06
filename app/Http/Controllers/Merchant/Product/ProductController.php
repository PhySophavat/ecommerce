<?php

namespace App\Http\Controllers\Merchant\Product;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Show merchant dashboard
     */
    public function dashboard(): View
    {
        return $this->page('dashboard', 'Merchant | Dashboard');
    }

    /**
     * Show list of merchant's products
     */
    public function index(Request $request): View
    {
        return $this->page('products', 'Merchant | My Products');
    }

    /**
     * Show create product form
     */
    public function create(): View
    {
        return $this->page('add-product', 'Merchant | Create Product');
    }

    /**
     * Store new product
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'sku' => 'required|string|unique:products,sku|max:100',
            'price' => 'required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'inventory' => 'required|integer|min:0',
            'tagline' => 'nullable|string|max:255',
            'description' => 'required|string',
        ]);

        // Generate unique slug
        $slug = Str::slug($validated['name']);
        $originalSlug = $slug;
        $counter = 1;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        $product = auth()->user()->products()->create([
            ...$validated,
            'slug' => $slug,
            'status' => 'pending', // Default to pending
            'merchant_id' => auth()->id(),
        ]);

        return redirect()->route('merchant.products.show', $product)
            ->with('success', 'Product created successfully. It is pending admin review.');
    }

    /**
     * Show product details
     */
    public function show(Product $product): View
    {
        $this->authorizeProduct($product);

        return $this->page('products', 'Merchant | Product Details');
    }

    /**
     * Show edit product form
     */
    public function edit(Product $product): View
    {
        $this->authorizeProduct($product);

        return redirect("/merchant/products/create?edit={$product->id}");
    }

    /**
     * Update product
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeProduct($product);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'sku' => 'required|string|unique:products,sku,' . $product->id . '|max:100',
            'price' => 'required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'inventory' => 'required|integer|min:0',
            'tagline' => 'nullable|string|max:255',
            'description' => 'required|string',
        ]);

        // Generate new slug if name changed
        $slug = $product->slug;
        if ($product->name !== $validated['name']) {
            $newSlug = Str::slug($validated['name']);
            $originalSlug = $newSlug;
            $counter = 1;
            while (Product::where('slug', $newSlug)->where('id', '!=', $product->id)->exists()) {
                $newSlug = $originalSlug . '-' . $counter;
                $counter++;
            }
            $slug = $newSlug;
        }

        // If product was approved, reset to pending
        $status = $product->status;
        if ($product->status === 'approved') {
            $status = 'pending';
        }

        $product->update([
            ...$validated,
            'slug' => $slug,
            'status' => $status,
        ]);

        return redirect()->route('merchant.products.show', $product)
            ->with('success', 'Product updated. Status reset to pending for re-review.');
    }

    /**
     * Delete product
     */
    public function destroy(Product $product): RedirectResponse
    {
        $this->authorizeProduct($product);

        $product->delete();

        return redirect()->route('merchant.products.index')
            ->with('success', 'Product deleted successfully.');
    }

    /**
     * Show pending products
     */
    public function pending(): View
    {
        return $this->page('merchant-pending-products', 'Merchant | Pending Products');
    }

    /**
     * Show rejected products
     */
    public function rejected(): View
    {
        return $this->page('merchant-rejected-products', 'Merchant | Rejected Products');
    }

    /**
     * Show approved products
     */
    public function approved(): View
    {
        return $this->page('merchant-approved-products', 'Merchant | Approved Products');
    }

    /**
     * Authorize that the product belongs to the current merchant
     */
    private function authorizeProduct(Product $product): void
    {
        if ($product->merchant_id !== auth()->id()) {
            abort(403, 'Unauthorized access.');
        }
    }

    private function page(string $screen, string $title): View
    {
        return view('backend.products.index', [
            'title' => $title,
            'context' => [
                'app' => 'backend-products',
                'screen' => $screen,
                'endpoint' => '/api/admin/products',
                'resource_base' => '/api/admin/products',
                'role_scope' => 'merchant',
            ],
        ]);
    }
}
