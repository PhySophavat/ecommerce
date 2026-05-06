<?php

namespace App\Http\Controllers\Backend\Order;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        return $this->page('orders');
    }

    public function pending(): View
    {
        return $this->page('pending-orders', 'pending');
    }

    public function processing(): View
    {
        return $this->page('processing-orders', 'processing');
    }

    public function shipped(): View
    {
        return $this->page('shipped-orders', 'shipped');
    }

    public function delivered(): View
    {
        return $this->page('delivered-orders', 'delivered');
    }

    public function cancelled(): View
    {
        return $this->page('cancelled-orders', 'cancelled');
    }

    public function refunded(): View
    {
        return $this->page('returns-refunds', 'refunded');
    }

    private function page(string $screen, ?string $status = null): View
    {
        return view('backend.orders.index', [
            'title' => 'Admin | Orders',
            'context' => [
                'app' => 'backend-orders',
                'screen' => $screen,
                'endpoint' => '/api/admin/orders',
                'role_scope' => 'admin',
                'initial_status' => $status,
            ],
        ]);
    }
}
