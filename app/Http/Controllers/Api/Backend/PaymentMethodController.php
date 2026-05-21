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
                'customer_text' => 'Customer is redirected to the gateway-ready payment page with an ABA QR placeholder.',
                'requires_reference' => false,
                'verification' => 'Webhook or mock callback confirms payment',
            ],
            [
                'code' => 'acleda',
                'label' => 'ACLEDA',
                'customer_text' => 'Customer is redirected to the gateway-ready payment page with an ACLEDA placeholder.',
                'requires_reference' => false,
                'verification' => 'Webhook or mock callback confirms payment',
            ],
            [
                'code' => 'wing',
                'label' => 'Wing',
                'customer_text' => 'Customer is redirected to the gateway-ready payment page with a Wing placeholder.',
                'requires_reference' => false,
                'verification' => 'Webhook or mock callback confirms payment',
            ],
            [
                'code' => 'card',
                'label' => 'Card',
                'customer_text' => 'Card payment is routed through the same gateway-ready payment session.',
                'requires_reference' => false,
                'verification' => 'Webhook or mock callback confirms payment',
            ],
        ])->map(function (array $method): array {
            $query = Order::query()->where('payment_method', $method['code']);

            return [
                ...$method,
                'orders_count' => (clone $query)->count(),
                'paid_count' => (clone $query)->whereIn('payment_status', ['paid', 'approved'])->count(),
                'pending_count' => (clone $query)->where('payment_status', 'pending')->count(),
                'failed_count' => (clone $query)->where('payment_status', 'failed')->count(),
                'cancelled_count' => (clone $query)->whereIn('payment_status', ['expired', 'cancelled'])->count(),
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
