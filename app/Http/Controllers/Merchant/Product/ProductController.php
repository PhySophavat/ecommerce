<?php

namespace App\Http\Controllers\Merchant\Product;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Contracts\View\View;

class ProductController extends Controller
{
    /**
     * Show merchant dashboard
     */
    public function dashboard(): View
    {
        $user = auth()->user();
        
        $stats = [
            'total_products' => $user->products()->count(),
            'pending_products' => $user->products()->where('status', 'pending')->count(),
            'approved_products' => $user->products()->where('status', 'approved')->count(),
            'rejected_products' => $user->products()->where('status', 'rejected')->count(),
        ];

        return view('merchant.dashboard', [
            'title' => 'Merchant | Dashboard',
            'stats' => $stats,
        ]);
    }

    /**
     * Show list of merchant's products
     */
    public function index(Request $request): View
    {
        $query = auth()->user()->products()
            ->with(['category'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('sku', 'like', '%' . $request->search . '%');
            });
        }

        $products = $query->paginate(20);

        return view('merchant.products.index', [
            'title' => 'Merchant | My Products',
            'products' => $products,
        ]);
    }

    /**
     * Show create product form
     */
    public function create(): View
    {
        $categories = Category::all();
        
        return view('merchant.products.create', [
            'title' => 'Merchant | Create Product',
            'categories' => $categories,
        ]);
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

        return view('merchant.products.show', [
            'title' => 'Merchant | Product Details',
            'product' => $product->load(['category', 'images', 'variants']),
        ]);
    }

    /**
     * Show edit product form
     */
    public function edit(Product $product): View
    {
        $this->authorizeProduct($product);

        $categories = Category::all();

        return view('merchant.products.edit', [
            'title' => 'Merchant | Edit Product',
            'product' => $product,
            'categories' => $categories,
        ]);
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
        $products = auth()->user()->products()
            ->with(['category'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('merchant.products.index', [
            'title' => 'Merchant | Pending Products',
            'products' => $products,
            'filter' => 'pending',
        ]);
    }

    /**
     * Show rejected products
     */
    public function rejected(): View
    {
        $products = auth()->user()->products()
            ->with(['category'])
            ->where('status', 'rejected')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('merchant.products.index', [
            'title' => 'Merchant | Rejected Products',
            'products' => $products,
            'filter' => 'rejected',
        ]);
    }

    /**
     * Show approved products
     */
    public function approved(): View
    {
        $products = auth()->user()->products()
            ->with(['category'])
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('merchant.products.index', [
            'title' => 'Merchant | Approved Products',
            'products' => $products,
            'filter' => 'approved',
        ]);
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
}