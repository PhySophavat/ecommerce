<?php

namespace App\Http\Controllers\Api\Backend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\AdminDashboardData;
use Illuminate\Http\JsonResponse;

class PaymentMethodController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $methods = collect([
            [
                'code' => 'cash',
                'label' => 'Cash',
                'customer_text' => 'Cash on delivery or manual settlement.',
                'requires_reference' => false,
                'verification' => 'Manual on delivery',
            ],
            [
                'code' => 'aba_qr',
                'label' => 'ABA QR',
                'customer_text' => 'Customer pays with ABA QR transfer.',
                'requires_reference' => true,
                'verification' => 'Admin verifies transfer reference',
            ],
            [
                'code' => 'wing',
                'label' => 'Wing',
                'customer_text' => 'Customer pays with Wing wallet.',
                'requires_reference' => true,
                'verification' => 'Admin or merchant verifies transfer reference',
            ],
            [
                'code' => 'card',
                'label' => 'Card',
                'customer_text' => 'Card payment is available when enabled.',
                'requires_reference' => true,
                'verification' => 'Manual verification before marked paid',
            ],
        ])->map(function (array $method): array {
            $query = Order::query()->where('payment_method', $method['code']);

            return [
                ...$method,
                'orders_count' => (clone $query)->count(),
                'paid_count' => (clone $query)->where('payment_status', 'paid')->count(),
                'unpaid_count' => (clone $query)->where('payment_status', 'unpaid')->count(),
                'failed_count' => (clone $query)->where('payment_status', 'failed')->count(),
                'refunded_count' => (clone $query)->where('payment_status', 'refunded')->count(),
            ];
        })->values();

        return response()->json([
            ...AdminDashboardData::paymentMethodsPage(),
            'summary' => [
                'methods' => $methods->count(),
                'active_methods' => $methods->count(),
                'reference_required' => $methods->where('requires_reference', true)->count(),
                'customer_orders' => Order::query()->whereNotNull('customer_id')->count(),
            ],
            'methods' => $methods->all(),
        ]);
    }
}
