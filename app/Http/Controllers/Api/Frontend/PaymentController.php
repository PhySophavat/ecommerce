<?php

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaymentGatewayService;
use App\Support\OrderData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayService $paymentGatewayService,
    ) {
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
        ]);

        $order = Order::query()->whereKey($validated['order_id'])->firstOrFail();
        abort_unless($order->customer_id === $request->user()->id, 403, 'Unauthorized access.');

        $result = $this->paymentGatewayService->create($order);

        return response()->json([
            'message' => 'Payment page prepared successfully.',
            'order' => OrderData::detail($order->fresh(['customer', 'items.merchant.user', 'items.product'])),
            'payment' => $this->paymentPayload($result['payment']),
            'checkout' => $result['checkout'],
        ], 201);
    }

    public function submitProof(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->customer_id === $request->user()->id, 403, 'Unauthorized access.');

        $validated = $request->validate([
            'transaction_ref' => ['nullable', 'string', 'max:120'],
            'payment_note' => ['nullable', 'string', 'max:1000'],
            'screenshot' => ['required', 'image', 'max:4096'],
        ]);

        $payment = $this->paymentGatewayService->submitPaymentProof(
            $order,
            $validated,
            $request->file('screenshot'),
        );

        $message = $payment->status === 'approved'
            ? 'Payment verified successfully.'
            : 'Payment verification failed. Please upload a clearer screenshot.';

        return response()->json([
            'message' => $message,
            'order' => OrderData::detail($order->fresh(['customer', 'items.merchant.user', 'items.product'])),
            'payment' => $this->paymentPayload($payment),
        ]);
    }

    public function webhook(Request $request): JsonResponse
    {
        $result = $this->paymentGatewayService->handleWebhook(
            $request->all(),
            $request->headers->all(),
        );

        return response()->json([
            'message' => $result['duplicate'] ? 'Payment notification already processed.' : 'Payment notification processed successfully.',
            'payment_status' => $result['payment']->status,
            'order_status' => $result['order']->order_status,
        ]);
    }

    public function status(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->customer_id === $request->user()->id, 403, 'Unauthorized access.');

        $payment = $this->paymentGatewayService->ensurePaymentRecord($order);

        return response()->json([
            'order' => [
                'id' => $order->id,
                'order_code' => $order->order_code,
                'status' => $order->status,
                'order_status' => $order->order_status,
                'payment_method' => $order->payment_method,
                'payment_type' => $order->payment_type,
                'payment_status' => $order->payment_status,
                'total_amount' => number_format((float) $order->total_amount, 2, '.', ''),
            ],
            'payment' => $this->paymentPayload($payment),
            'checkout' => $this->paymentGatewayService->create($order)['checkout'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentPayload($payment): array
    {
        return [
            'id' => $payment->id,
            'payment_method' => $payment->payment_method ?: $payment->provider,
            'provider' => $payment->provider,
            'transaction_id' => $payment->transaction_id,
            'transaction_ref' => $payment->transaction_ref ?: $payment->transaction_reference,
            'gateway_reference' => $payment->gateway_reference,
            'screenshot_url' => $payment->screenshot ? Storage::disk('public')->url($payment->screenshot) : ($payment->screenshot_path ? Storage::disk('public')->url($payment->screenshot_path) : null),
            'admin_note' => $payment->admin_note,
            'auto_check_status' => $payment->auto_check_status ?: 'pending',
            'auto_check_score' => $payment->auto_check_score,
            'auto_check_result' => $payment->auto_check_result,
            'ocr_text' => $payment->ocr_text,
            'auto_checked_at' => $payment->auto_checked_at?->toIso8601String(),
            'approved_at' => $payment->approved_at?->toIso8601String(),
            'amount' => number_format((float) $payment->amount, 2, '.', ''),
            'currency' => $payment->currency,
            'status' => $payment->status,
            'verified_at' => $payment->verified_at?->toIso8601String(),
            'verified_by' => $payment->verified_by,
        ];
    }
}
