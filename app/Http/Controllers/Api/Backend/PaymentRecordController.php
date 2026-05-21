<?php

namespace App\Http\Controllers\Api\Backend;

use App\Http\Controllers\Controller;
use App\Models\GatewayPayment;
use App\Services\PaymentGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentRecordController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayService $paymentGatewayService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $payments = GatewayPayment::query()
            ->with(['order.customer', 'verifier'])
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')->toString())
            )
            ->when(
                $request->filled('payment_method'),
                fn ($query) => $query->where('payment_method', $request->string('payment_method')->toString())
            )
            ->latest('created_at')
            ->latest('id')
            ->get();

        return response()->json([
            'payments' => $payments->map(fn (GatewayPayment $payment): array => $this->paymentPayload($payment))->values()->all(),
        ]);
    }

    public function show(GatewayPayment $payment): JsonResponse
    {
        $payment->load(['order.customer', 'verifier']);

        return response()->json([
            'payment' => $this->paymentPayload($payment, true),
        ]);
    }

    public function approve(Request $request, GatewayPayment $payment): JsonResponse
    {
        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $payment = $this->paymentGatewayService->approvePayment(
            $payment,
            $request->user(),
            $validated['admin_note'] ?? null,
        );

        return response()->json([
            'message' => 'Payment approved successfully.',
            'payment' => $this->paymentPayload($payment, true),
        ]);
    }

    public function reject(Request $request, GatewayPayment $payment): JsonResponse
    {
        $validated = $request->validate([
            'admin_note' => ['required', 'string', 'max:1000'],
        ]);

        $payment = $this->paymentGatewayService->rejectPayment(
            $payment,
            $request->user(),
            $validated['admin_note'],
        );

        return response()->json([
            'message' => 'Payment rejected successfully.',
            'payment' => $this->paymentPayload($payment, true),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentPayload(GatewayPayment $payment, bool $withOrder = false): array
    {
        $payload = [
            'id' => $payment->id,
            'order_id' => $payment->order_id,
            'order_code' => $payment->order?->order_code,
            'order_number' => $payment->order?->number,
            'customer_name' => $payment->order?->customer_name,
            'customer_email' => $payment->order?->email,
            'payment_method' => $payment->payment_method ?: $payment->provider,
            'amount' => number_format((float) $payment->amount, 2, '.', ''),
            'status' => $payment->status,
            'auto_check_status' => $payment->auto_check_status ?: 'pending',
            'auto_check_score' => $payment->auto_check_score,
            'approved_at' => $payment->approved_at?->toIso8601String(),
            'transaction_ref' => $payment->transaction_ref ?: $payment->transaction_reference,
            'screenshot_url' => $payment->screenshot ? Storage::disk('public')->url($payment->screenshot) : ($payment->screenshot_path ? Storage::disk('public')->url($payment->screenshot_path) : null),
            'admin_note' => $payment->admin_note,
            'verified_at' => $payment->verified_at?->toIso8601String(),
            'verified_by' => $payment->verified_by,
            'verified_by_name' => $payment->verifier?->name,
            'order_status' => $payment->order?->order_status,
            'created_at' => $payment->created_at?->toIso8601String(),
            'can_approve' => $payment->status === 'submitted',
            'can_reject' => $payment->status === 'submitted',
        ];

        if ($withOrder) {
            $payload['notes'] = $payment->order?->notes;
            $payload['ocr_text'] = $payment->ocr_text;
            $payload['auto_check_result'] = $payment->auto_check_result;
            $payload['auto_checked_at'] = $payment->auto_checked_at?->toIso8601String();
        }

        return $payload;
    }
}
