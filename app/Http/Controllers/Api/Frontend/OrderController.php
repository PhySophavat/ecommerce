<?php

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\OrderData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = $request->user()
            ->orders()
            ->with(['items.merchant.user', 'items.product'])
            ->latest('placed_at')
            ->latest('id')
            ->get();

        return response()->json([
            'orders' => $orders->map(fn (Order $order): array => OrderData::listItem($order))->values()->all(),
        ]);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->customer_id === $request->user()->id, 403, 'Unauthorized access.');

        return response()->json([
            'order' => OrderData::detail($order->load(['customer', 'items.merchant.user', 'items.product'])),
        ]);
    }
}
