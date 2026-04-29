<?php

namespace App\Http\Controllers\Api\Backend;

use App\Http\Controllers\Controller;
use App\Models\MerchantDeposit;
use App\Services\DepositService;
use App\Support\AdminDashboardData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepositController extends Controller
{
    public function __construct(private readonly DepositService $depositService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $status = (string) $request->query('status', 'all');

        $query = MerchantDeposit::query()
            ->with('merchant.user')
            ->latest();

        if ($status !== '' && $status !== 'all') {
            $query->where('status', $status);
        }

        $deposits = $query->get();

        return response()->json([
            ...AdminDashboardData::depositsPage(),
            'summary' => [
                'all' => MerchantDeposit::query()->count(),
                'pending' => MerchantDeposit::query()->where('status', 'pending')->count(),
                'approved' => MerchantDeposit::query()->where('status', 'approved')->count(),
                'rejected' => MerchantDeposit::query()->where('status', 'rejected')->count(),
            ],
            'filters' => [
                ['label' => 'All', 'value' => 'all'],
                ['label' => 'Pending', 'value' => 'pending'],
                ['label' => 'Approved', 'value' => 'approved'],
                ['label' => 'Rejected', 'value' => 'rejected'],
            ],
            'selected_status' => $status,
            'deposits' => $deposits->map(fn (MerchantDeposit $deposit): array => $this->payload($deposit))->all(),
        ]);
    }

    public function approve(Request $request, MerchantDeposit $deposit): JsonResponse
    {
        $validated = $request->validate([
            'admin_note' => ['nullable', 'string'],
        ]);

        $deposit = $this->depositService->approve($deposit, $validated['admin_note'] ?? null);

        return response()->json([
            'message' => 'Deposit approved successfully.',
            'deposit' => $this->payload($deposit),
        ]);
    }

    public function reject(Request $request, MerchantDeposit $deposit): JsonResponse
    {
        $validated = $request->validate([
            'admin_note' => ['nullable', 'string'],
        ]);

        $deposit = $this->depositService->reject($deposit, $validated['admin_note'] ?? null);

        return response()->json([
            'message' => 'Deposit rejected successfully.',
            'deposit' => $this->payload($deposit),
        ]);
    }

    private function payload(MerchantDeposit $deposit): array
    {
        $deposit->loadMissing('merchant.user');

        return [
            'id' => $deposit->id,
            'amount' => number_format((float) $deposit->amount, 2, '.', ''),
            'payment_method' => $deposit->payment_method,
            'khqr_code' => $deposit->khqr_code,
            'payment_proof_url' => $this->depositService->proofUrl($deposit->payment_proof),
            'status' => $deposit->status,
            'note' => $deposit->note,
            'admin_note' => $deposit->admin_note,
            'merchant' => [
                'id' => $deposit->merchant?->id,
                'shop_name' => $deposit->merchant?->shop_name,
                'owner_name' => $deposit->merchant?->user?->name,
                'email' => $deposit->merchant?->user?->email,
            ],
            'created_at' => $deposit->created_at?->toIso8601String(),
            'approved_at' => $deposit->approved_at?->toIso8601String(),
            'rejected_at' => $deposit->rejected_at?->toIso8601String(),
        ];
    }
}
