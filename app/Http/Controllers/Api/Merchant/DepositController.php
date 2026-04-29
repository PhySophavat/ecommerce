<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\MerchantDeposit;
use App\Services\DepositService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepositController extends Controller
{
    public function __construct(private readonly DepositService $depositService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $merchant = $this->merchant($request);
        $deposits = $merchant->deposits()->latest()->get();

        return response()->json([
            'khqr' => [
                'code' => (string) config('merchant_wallet.khqr_code'),
                'image_url' => config('merchant_wallet.khqr_image_url'),
            ],
            'deposits' => $deposits->map(fn (MerchantDeposit $deposit): array => $this->payload($deposit))->all(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $merchant = $this->merchant($request);
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', Rule::in(['khqr', 'bank_transfer'])],
            'payment_proof' => ['required', 'image', 'max:4096'],
            'note' => ['nullable', 'string'],
        ]);

        $deposit = $this->depositService->create(
            $merchant,
            round((float) $validated['amount'], 2),
            $validated['payment_method'],
            $request->file('payment_proof'),
            $validated['note'] ?? null,
        );

        return response()->json([
            'message' => 'Deposit request submitted successfully.',
            'deposit' => $this->payload($deposit),
        ], 201);
    }

    private function merchant(Request $request): Merchant
    {
        return $request->user()->merchant()->firstOrFail();
    }

    private function payload(MerchantDeposit $deposit): array
    {
        return [
            'id' => $deposit->id,
            'amount' => number_format((float) $deposit->amount, 2, '.', ''),
            'payment_method' => $deposit->payment_method,
            'khqr_code' => $deposit->khqr_code,
            'payment_proof_url' => $this->depositService->proofUrl($deposit->payment_proof),
            'status' => $deposit->status,
            'note' => $deposit->note,
            'admin_note' => $deposit->admin_note,
            'created_at' => $deposit->created_at?->toIso8601String(),
            'approved_at' => $deposit->approved_at?->toIso8601String(),
            'rejected_at' => $deposit->rejected_at?->toIso8601String(),
        ];
    }
}
