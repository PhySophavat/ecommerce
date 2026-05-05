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
        $merchant = $this->merchant($request)->load('user');
        $deposits = $merchant->deposits()->latest()->get();

        return response()->json([
            'merchant' => [
                'shop_name' => $merchant->shop_name,
                'owner_name' => $merchant->user?->name,
            ],
            'providers' => $this->depositService->providers(),
            'deposits' => $deposits->map(fn (MerchantDeposit $deposit): array => $this->payload($deposit))->all(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $merchant = $this->merchant($request);
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'bank_name' => ['required', Rule::in(array_column($this->depositService->providers(), 'bank_name'))],
            'account_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:50'],
            'payment_proof' => ['required', 'image', 'max:4096'],
            'note' => ['nullable', 'string'],
        ]);

        $deposit = $this->depositService->create(
            $merchant,
            round((float) $validated['amount'], 2),
            $validated['bank_name'],
            $validated['account_name'],
            $validated['account_number'],
            $validated['phone_number'],
            $request->file('payment_proof'),
            $validated['note'] ?? null,
        );

        return response()->json([
            'message' => 'Deposit submitted and credited successfully.',
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
            'bank_name' => $deposit->bank_name,
            'account_name' => $deposit->account_name,
            'account_number' => $deposit->account_number,
            'phone_number' => $deposit->phone_number,
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
