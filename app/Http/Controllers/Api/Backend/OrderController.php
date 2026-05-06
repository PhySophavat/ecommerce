<?php

namespace App\Http\Controllers\Api\Backend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Support\OrderData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->whereNotNull('customer_id')
            ->with(['customer', 'items.merchant.user', 'items.product'])
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')->toString())
            )
            ->when(
                $request->filled('payment_status'),
                fn ($query) => $query->where('payment_status', $request->string('payment_status')->toString())
            )
            ->when(
                $request->filled('payment_method'),
                fn ($query) => $query->where('payment_method', $request->string('payment_method')->toString())
            )
            ->latest('placed_at')
            ->latest('id')
            ->get();

        return response()->json([
            'orders' => $orders->map(fn (Order $order): array => OrderData::listItem($order))->values()->all(),
        ]);
    }

    public function show(Order $order): JsonResponse
    {
        return response()->json([
            'order' => OrderData::detail($order->load(['customer', 'items.merchant.user', 'items.product'])),
        ]);
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,paid,processing,shipped,delivered,cancelled,refunded'],
        ]);

        $order = $this->orderService->updateOrderStatus($order, $validated['status']);

        return response()->json([
            'message' => 'Order status updated successfully.',
            'order' => OrderData::detail($order),
        ]);
    }

    public function updatePaymentStatus(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'payment_status' => ['required', 'in:unpaid,paid,failed,refunded'],
        ]);

        $order = $this->orderService->updatePaymentStatus($order, $validated['payment_status']);

        return response()->json([
            'message' => 'Payment status updated successfully.',
            'order' => OrderData::detail($order),
        ]);
    }
}
