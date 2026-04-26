<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class ProductManagementController extends Controller
{
    public function dashboard(): View
    {
        return $this->page('dashboard', 'Admin | Dashboard');
    }

    public function index(): View
    {
        return $this->page('products', 'Admin | Products');
    }

    public function create(): View
    {
        return $this->page('add-product', 'Admin | Add Product');
    }

    public function featured(): View
    {
        return $this->page('featured-products', 'Admin | Featured Products');
    }

    private function page(string $screen, string $title): View
    {
        return view('backend.products.index', [
            'title' => $title,
            'context' => [
                'app' => 'backend-products',
                'screen' => $screen,
            ],
        ]);
    }
}
