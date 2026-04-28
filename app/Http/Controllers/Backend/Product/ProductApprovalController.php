<?php

namespace App\Http\Controllers\Backend\Product;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

class ProductApprovalController extends Controller
{
    /**
     * Show pending products for admin review
     */
    public function pending(Request $request): View
    {
        $query = Product::with(['category', 'merchant'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc');

        // Filter by merchant
        if ($request->has('merchant_id') && $request->merchant_id) {
            $query->where('merchant_id', $request->merchant_id);
        }

        $products = $query->paginate(20);

        return view('backend.products.pending', [
            'title' => 'Admin | Pending Products',
            'products' => $products,
        ]);
    }

    /**
     * Approve a product
     */
    public function approve(Request $request, Product $product): RedirectResponse
    {
        $request->validate([
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $product->update([
            'status' => 'approved',
            'admin_note' => $request->input('admin_note'),
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Product approved successfully.');
    }

    /**
     * Reject a product
     */
    public function reject(Request $request, Product $product): RedirectResponse
    {
        $request->validate([
            'admin_note' => 'required|string|max:1000',
        ]);

        $product->update([
            'status' => 'rejected',
            'admin_note' => $request->input('admin_note'),
        ]);

        return redirect()->back()->with('success', 'Product rejected.');
    }

    /**
     * Show all products with filters
     */
    public function index(Request $request): View
    {
        $query = Product::with(['category', 'merchant'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by merchant
        if ($request->has('merchant_id') && $request->merchant_id) {
            $query->where('merchant_id', $request->merchant_id);
        }

        // Filter by search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('sku', 'like', '%' . $request->search . '%');
            });
        }

        $products = $query->paginate(20);

        return view('backend.products.index', [
            'title' => 'Admin | Products',
            'products' => $products,
        ]);
    }
}