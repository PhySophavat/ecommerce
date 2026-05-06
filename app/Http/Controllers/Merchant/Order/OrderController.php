<?php

namespace App\Http\Controllers\Merchant\Order;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        return $this->page('merchant-orders');
    }

    public function pending(): View
    {
        return $this->page('merchant-pending-orders', 'pending');
    }

    public function processing(): View
    {
        return $this->page('merchant-processing-orders', 'processing');
    }

    public function shipped(): View
    {
        return $this->page('merchant-shipped-orders', 'shipped');
    }

    public function delivered(): View
    {
        return $this->page('merchant-delivered-orders', 'delivered');
    }

    public function cancelled(): View
    {
        return $this->page('merchant-cancelled-orders', 'cancelled');
    }

    public function refunded(): View
    {
        return $this->page('merchant-refunded-orders', 'refunded');
    }

    private function page(string $screen, ?string $status = null): View
    {
        return view('backend.orders.index', [
            'title' => 'Merchant | Orders',
            'context' => [
                'app' => 'backend-orders',
                'screen' => $screen,
                'endpoint' => '/api/merchant/orders',
                'role_scope' => 'merchant',
                'initial_status' => $status,
            ],
        ]);
    }
}
