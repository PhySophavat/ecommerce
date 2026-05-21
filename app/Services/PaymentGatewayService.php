<?php

namespace App\Services;

use App\Models\GatewayPayment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentGatewayService
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly TelegramService $telegramService,
        private readonly PaymentVerificationService $paymentVerificationService,
    ) {
    }

    /**
     * @return array{payment: GatewayPayment, checkout: array<string, mixed>}
     */
    public function create(Order $order): array
    {
        $payment = $this->ensurePaymentRecord($order);

        return [
            'payment' => $payment,
            'checkout' => $this->checkoutPayload($order, $payment),
        ];
    }

    public function ensurePaymentRecord(Order $order): GatewayPayment
    {
        $payment = $order->payments()->latest('id')->first();

        if ($payment) {
            return $payment;
        }

        return $order->payments()->create([
            'payment_method' => $order->payment_method,
            'provider' => $order->payment_method,
            'transaction_id' => $this->generateTransactionId(),
            'gateway_reference' => $order->order_code,
            'transaction_ref' => $order->payment_reference,
            'transaction_reference' => $order->payment_reference,
            'screenshot' => null,
            'screenshot_path' => null,
            'admin_note' => null,
            'reject_reason' => null,
            'verified_by' => null,
            'verified_at' => null,
            'auto_check_status' => 'pending',
            'auto_check_score' => null,
            'auto_check_result' => null,
            'ocr_text' => null,
            'auto_checked_at' => null,
            'approved_at' => null,
            'amount' => round((float) $order->total_amount, 2),
            'currency' => 'USD',
            'status' => $this->initialStatus($order),
            'paid_at' => null,
            'raw_response' => [
                'created_via' => 'order_checkout',
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function submitPaymentProof(Order $order, array $payload, UploadedFile $screenshot): GatewayPayment
    {
        if ($order->payment_type !== 'manual_transfer') {
            throw ValidationException::withMessages([
                'payment_method' => ['Only manual-transfer orders accept payment proof uploads.'],
            ]);
        }

        $payment = $this->ensurePaymentRecord($order);

        if ($payment->status === 'approved') {
            throw ValidationException::withMessages([
                'status' => ['This payment has already been approved.'],
            ]);
        }

        $screenshotPath = $screenshot->store('payment-proofs', 'public');
        $transactionRef = trim((string) ($payload['transaction_ref'] ?? ''));

        $payment->forceFill([
            'payment_method' => $order->payment_method,
            'provider' => $order->payment_method,
            'transaction_ref' => $transactionRef !== '' ? $transactionRef : null,
            'transaction_reference' => $transactionRef !== '' ? $transactionRef : null,
            'screenshot' => $screenshotPath,
            'screenshot_path' => $screenshotPath,
            'admin_note' => null,
            'reject_reason' => null,
            'verified_by' => null,
            'verified_at' => null,
            'auto_check_status' => 'pending',
            'auto_check_score' => null,
            'auto_check_result' => null,
            'ocr_text' => null,
            'auto_checked_at' => null,
            'approved_at' => null,
            'status' => 'submitted',
            'raw_response' => [
                'submitted_via' => 'customer_payment_page',
            ],
        ])->save();

        $absoluteImagePath = Storage::disk('public')->path($screenshotPath);
        $autoCheck = $this->paymentVerificationService->verify($payment->fresh(), $absoluteImagePath);

        $payment->forceFill($autoCheck)->save();

        if (($payment->auto_check_status ?? '') === 'auto_verified' && (int) ($payment->auto_check_score ?? 0) >= 80) {
            $approvedAt = now();

            $payment->forceFill([
                'status' => 'approved',
                'approved_at' => $approvedAt,
                'verified_at' => $approvedAt,
                'paid_at' => $approvedAt,
            ])->save();

            $order->forceFill([
                'payment_reference' => $payment->transaction_ref,
                'payment_notes' => $payload['payment_note'] ?? $order->payment_notes,
                'payment_status' => 'approved',
                'order_status' => 'processing',
                'status' => 'processing',
                'paid_at' => $approvedAt,
            ])->save();
        } else {
            $payment->forceFill([
                'status' => 'auto_failed',
                'approved_at' => null,
                'verified_at' => null,
                'paid_at' => null,
            ])->save();

            $order->forceFill([
                'payment_reference' => $payment->transaction_ref,
                'payment_notes' => $payload['payment_note'] ?? $order->payment_notes,
                'payment_status' => 'auto_failed',
                'order_status' => 'pending_payment',
                'status' => 'pending',
                'paid_at' => null,
            ])->save();
        }

        $this->telegramService->notifyPaymentProofSubmitted($order->fresh('customer'), $payment->fresh());

        return $payment->fresh();
    }

    public function approvePayment(GatewayPayment $payment, User $admin, ?string $note = null): GatewayPayment
    {
        $payment->loadMissing('order');

        $payment->forceFill([
            'status' => 'approved',
            'admin_note' => $note,
            'reject_reason' => null,
            'verified_by' => $admin->id,
            'verified_at' => now(),
            'paid_at' => now(),
        ])->save();

        $payment->order->forceFill([
            'payment_reference' => $payment->transaction_ref,
            'payment_status' => 'approved',
            'order_status' => 'processing',
            'status' => 'processing',
            'paid_at' => $payment->verified_at,
        ])->save();

        app(FinanceReportingService::class)->syncOrder($payment->order->fresh(['items']));

        return $payment->fresh();
    }

    public function rejectPayment(GatewayPayment $payment, User $admin, string $note): GatewayPayment
    {
        $payment->loadMissing('order');

        $payment->forceFill([
            'status' => 'rejected',
            'admin_note' => $note,
            'reject_reason' => $note,
            'verified_by' => $admin->id,
            'verified_at' => now(),
            'paid_at' => null,
        ])->save();

        $payment->order->forceFill([
            'payment_status' => 'rejected',
            'order_status' => 'pending_payment',
            'status' => 'pending',
            'paid_at' => null,
        ])->save();

        app(FinanceReportingService::class)->syncOrder($payment->order->fresh(['items']));

        return $payment->fresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $headers
     * @return array{payment: GatewayPayment, order: Order, duplicate: bool}
     */
    public function handleWebhook(array $payload, array $headers = []): array
    {
        $provider = strtolower((string) ($payload['provider'] ?? $this->provider()));

        if ($provider !== 'mock') {
            $this->verifyRealSignature($payload, $headers);
        }

        $orderCode = (string) ($payload['order_code'] ?? '');
        $transactionId = (string) ($payload['transaction_id'] ?? '');
        $amount = round((float) ($payload['amount'] ?? 0), 2);
        $currency = strtoupper((string) ($payload['currency'] ?? 'USD'));
        $status = strtolower((string) ($payload['status'] ?? 'pending'));

        $order = Order::query()->where('order_code', $orderCode)->first();

        if (!$order) {
            throw ValidationException::withMessages([
                'order_code' => ['Unknown order code supplied to payment webhook.'],
            ]);
        }

        $payment = $this->ensurePaymentRecord($order);

        if ($transactionId !== '' && $payment->transaction_id !== $transactionId) {
            throw ValidationException::withMessages([
                'transaction_id' => ['Unknown transaction supplied to payment webhook.'],
            ]);
        }

        if ($amount !== round((float) $payment->amount, 2)) {
            throw ValidationException::withMessages([
                'amount' => ['Payment amount does not match the order total.'],
            ]);
        }

        if ($currency !== strtoupper((string) $payment->currency)) {
            throw ValidationException::withMessages([
                'currency' => ['Payment currency does not match the order currency.'],
            ]);
        }

        $duplicate = false;

        if ($payment->status === 'approved' && in_array($status, ['approved', 'paid', 'success'], true)) {
            $duplicate = true;
        } else {
            $normalized = $this->normalizeStatus($status);

            $payment->forceFill([
                'gateway_reference' => (string) ($payload['gateway_reference'] ?? $payment->gateway_reference),
                'status' => $normalized,
                'verified_at' => $normalized === 'approved' ? now() : null,
                'paid_at' => $normalized === 'approved' ? now() : null,
                'raw_response' => [
                    'headers' => $headers,
                    'payload' => $payload,
                ],
            ])->save();

            if ($payment->status === 'approved') {
                $order = $this->orderService->markPaymentPaid($order, $payment->verified_at);
            } elseif (in_array($payment->status, ['failed', 'rejected'], true)) {
                $order = $this->orderService->markPaymentOutcome($order, $payment->status);
            }
        }

        return [
            'payment' => $payment->fresh(),
            'order' => $order->fresh(),
            'duplicate' => $duplicate,
        ];
    }

    public function simulatePaid(GatewayPayment $payment): GatewayPayment
    {
        if (!$this->isMock()) {
            throw ValidationException::withMessages([
                'provider' => ['Mock payment simulation is only available in mock mode.'],
            ]);
        }

        $payment->loadMissing('order');

        $this->handleWebhook([
            'provider' => 'mock',
            'order_code' => $payment->order->order_code,
            'transaction_id' => $payment->transaction_id,
            'gateway_reference' => $payment->gateway_reference ?: $this->generateGatewayReference(),
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'status' => 'approved',
            'signature' => 'mock-signature',
        ]);

        return $payment->fresh();
    }

    public function methodBehavior(string $method): array
    {
        $paymentType = match ($method) {
            'cash' => 'cash',
            'aba_qr', 'acleda', 'wing' => 'manual_transfer',
            default => 'gateway',
        };

        $badge = match ($paymentType) {
            'cash' => 'Pay later',
            'manual_transfer' => 'Manual verification',
            default => $this->isGatewayAvailable() ? 'Live gateway' : 'Coming soon',
        };

        return [
            'payment_type' => $paymentType,
            'badge' => $badge,
            'enabled' => $paymentType !== 'gateway' || $this->isGatewayAvailable(),
        ];
    }

    public function provider(): string
    {
        return strtolower((string) config('payment_gateway.provider', 'mock'));
    }

    public function mode(): string
    {
        return strtolower((string) config('payment_gateway.mode', 'sandbox'));
    }

    public function isMock(): bool
    {
        return $this->provider() === 'mock';
    }

    public function isGatewayAvailable(): bool
    {
        return !$this->isMock()
            && filled(config('payment_gateway.merchant_id'))
            && filled(config('payment_gateway.api_key'))
            && filled(config('payment_gateway.secret_key'))
            && filled(config('payment_gateway.base_url'));
    }

    /**
     * @return array<string, mixed>
     */
    private function checkoutPayload(Order $order, GatewayPayment $payment): array
    {
        $account = $this->paymentAccountMeta($order->payment_method);

        return [
            'payment_url' => rtrim((string) config('payment_gateway.frontend_url'), '/').'#/payment/'.$order->id,
            'qr_payload' => sprintf('manual://pay/%s/%s', $order->order_code, $payment->transaction_id),
            'qr_image_url' => '/khqr.jpg',
            'account_name' => $account['name'],
            'account_number' => $account['number'],
            'account_label' => $account['label'],
            'pay_amount' => number_format((float) $order->total_amount, 2, '.', ''),
        ];
    }

    /**
     * @return array{name: string, number: string, label: string}
     */
    private function paymentAccountMeta(string $method): array
    {
        return match ($method) {
            'aba_qr' => ['name' => 'E-Commerce ABA Collection', 'number' => '001 234 567', 'label' => 'ABA QR'],
            'acleda' => ['name' => 'E-Commerce ACLEDA Account', 'number' => '091 002 884', 'label' => 'ACLEDA'],
            'wing' => ['name' => 'E-Commerce Wing Wallet', 'number' => '012 555 909', 'label' => 'Wing'],
            'card' => ['name' => 'E-Commerce Card Gateway', 'number' => 'CARD', 'label' => 'Card'],
            default => ['name' => 'Cash payment', 'number' => '-', 'label' => 'Cash'],
        };
    }

    private function initialStatus(Order $order): string
    {
        return match ($order->payment_type) {
            'cash' => 'pending',
            default => 'pending',
        };
    }

    private function generateTransactionId(): string
    {
        do {
            $value = 'TXN-'.now()->format('YmdHis').'-'.Str::upper(Str::random(8));
        } while (GatewayPayment::query()->where('transaction_id', $value)->exists());

        return $value;
    }

    private function generateGatewayReference(): string
    {
        return 'REF-'.Str::upper(Str::random(10));
    }

    private function normalizeStatus(string $value): string
    {
        return match (strtolower($value)) {
            'approved', 'paid', 'success' => 'approved',
            'submitted' => 'submitted',
            'rejected' => 'rejected',
            'failed' => 'failed',
            default => 'pending',
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $headers
     */
    private function verifyRealSignature(array $payload, array $headers): void
    {
        $secret = (string) config('payment_gateway.secret_key');
        $signature = (string) (Arr::get($headers, 'x-payment-signature')
            ?? Arr::get($headers, 'X-Payment-Signature')
            ?? ($payload['signature'] ?? ''));

        if ($secret === '' || $signature === '') {
            throw ValidationException::withMessages([
                'signature' => ['Missing gateway signature configuration.'],
            ]);
        }

        $expected = hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES), $secret);

        if (!hash_equals($expected, $signature)) {
            throw ValidationException::withMessages([
                'signature' => ['Invalid payment gateway signature.'],
            ]);
        }
    }
}
