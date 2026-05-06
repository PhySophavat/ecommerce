<?php

namespace App\Http\Controllers\Api\Merchant;

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
        $merchantId = $request->user()->merchant?->id;

        abort_unless($merchantId, 403, 'Merchant profile not found.');

        $orders = Order::query()
            ->whereNotNull('customer_id')
            ->whereHas('items', fn ($query) => $query->where('merchant_id', $merchantId))
            ->with([
                'customer',
                'items' => fn ($query) => $query->where('merchant_id', $merchantId)->with(['merchant.user', 'product']),
            ])
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

    public function show(Request $request, Order $order): JsonResponse
    {
        $merchantId = $request->user()->merchant?->id;

        abort_unless($merchantId, 403, 'Merchant profile not found.');
        abort_unless(
            $order->items()->where('merchant_id', $merchantId)->exists(),
            403,
            'Unauthorized access.'
        );

        $order->load([
            'customer',
            'items' => fn ($query) => $query->where('merchant_id', $merchantId)->with(['merchant.user', 'product']),
        ]);

        return response()->json([
            'order' => OrderData::detail($order),
        ]);
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $merchantId = $request->user()->merchant?->id;

        abort_unless($merchantId, 403, 'Merchant profile not found.');
        abort_unless(
            $order->items()->where('merchant_id', $merchantId)->exists(),
            403,
            'Unauthorized access.'
        );

        $validated = $request->validate([
            'status' => ['required', 'in:pending,processing,shipped,delivered,cancelled'],
        ]);

        $order = $this->orderService->updateOrderStatus($order, $validated['status']);

        return response()->json([
            'message' => 'Order status updated successfully.',
            'order' => OrderData::detail($order),
        ]);
    }
}
