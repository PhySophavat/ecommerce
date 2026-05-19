<?php

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use App\Support\OrderData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CheckoutController extends Controller
{
    public function __construct(private readonly OrderService $orderService)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:40'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:80'],
            'postal_code' => ['required', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
            'payment_method' => ['required', 'in:cash,aba_qr,acleda,wing,card'],
            'payment_reference' => [
                'nullable',
                'string',
                'max:120',
                Rule::requiredIf(fn () => $request->input('payment_method') !== 'cash'),
            ],
            'payment_notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $order = $this->orderService->checkout($request->user(), $validated);

        return response()->json([
            'message' => 'Order placed successfully.',
            'order' => OrderData::detail($order),
        ], 201);
    }
}
