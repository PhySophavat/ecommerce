<?php

namespace App\Http\Controllers\Api\Backend;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Support\AdminDashboardData;
use App\Services\WithdrawalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    public function __construct(private readonly WithdrawalService $withdrawalService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');

        $query = Withdrawal::query()
            ->with(['merchant.user', 'bankAccount'])
            ->latest();

        if (is_string($status) && $status !== '' && $status !== 'all') {
            $query->where('status', $status);
        }

        $withdrawals = $query->get();

        return response()->json([
            ...AdminDashboardData::withdrawalsPage(),
            'summary' => [
                'all' => Withdrawal::query()->count(),
                'pending' => Withdrawal::query()->where('status', 'pending')->count(),
                'approved' => Withdrawal::query()->where('status', 'approved')->count(),
                'rejected' => Withdrawal::query()->where('status', 'rejected')->count(),
                'paid' => Withdrawal::query()->where('status', 'paid')->count(),
            ],
            'filters' => [
                ['label' => 'All', 'value' => 'all'],
                ['label' => 'Pending', 'value' => 'pending'],
                ['label' => 'Approved', 'value' => 'approved'],
                ['label' => 'Rejected', 'value' => 'rejected'],
                ['label' => 'Paid', 'value' => 'paid'],
            ],
            'selected_status' => $status ?: 'all',
            'withdrawals' => $withdrawals->map(fn (Withdrawal $withdrawal): array => $this->payload($withdrawal))->all(),
        ]);
    }

    public function approve(Request $request, Withdrawal $withdrawal): JsonResponse
    {
        $validated = $request->validate([
            'note' => ['nullable', 'string'],
        ]);

        $withdrawal = $this->withdrawalService->approve($withdrawal, $validated['note'] ?? null);

        return response()->json([
            'message' => 'Withdrawal approved successfully.',
            'withdrawal' => $this->payload($withdrawal),
        ]);
    }

    public function reject(Request $request, Withdrawal $withdrawal): JsonResponse
    {
        $validated = $request->validate([
            'note' => ['nullable', 'string'],
        ]);

        $withdrawal = $this->withdrawalService->reject($withdrawal, $validated['note'] ?? null);

        return response()->json([
            'message' => 'Withdrawal rejected successfully.',
            'withdrawal' => $this->payload($withdrawal),
        ]);
    }

    public function markPaid(Request $request, Withdrawal $withdrawal): JsonResponse
    {
        $validated = $request->validate([
            'note' => ['nullable', 'string'],
        ]);

        $withdrawal = $this->withdrawalService->markPaid($withdrawal, $validated['note'] ?? null);

        return response()->json([
            'message' => 'Withdrawal marked as paid.',
            'withdrawal' => $this->payload($withdrawal),
        ]);
    }

    private function payload(Withdrawal $withdrawal): array
    {
        $withdrawal->loadMissing(['merchant.user', 'bankAccount']);

        return [
            'id' => $withdrawal->id,
            'amount' => number_format((float) $withdrawal->amount, 2, '.', ''),
            'fee_amount' => number_format((float) $withdrawal->fee_amount, 2, '.', ''),
            'net_amount' => number_format((float) $withdrawal->net_amount, 2, '.', ''),
            'status' => $withdrawal->status,
            'note' => $withdrawal->note,
            'merchant' => [
                'id' => $withdrawal->merchant?->id,
                'shop_name' => $withdrawal->merchant?->shop_name,
                'owner_name' => $withdrawal->merchant?->user?->name,
                'email' => $withdrawal->merchant?->user?->email,
            ],
            'bank_account' => [
                'id' => $withdrawal->bankAccount?->id,
                'bank_name' => $withdrawal->bankAccount?->bank_name,
                'account_name' => $withdrawal->bankAccount?->account_name,
                'account_number' => $withdrawal->bankAccount?->maskedAccountNumber(),
                'account_type' => $withdrawal->bankAccount?->account_type,
            ],
            'created_at' => $withdrawal->created_at?->toIso8601String(),
            'approved_at' => $withdrawal->approved_at?->toIso8601String(),
            'rejected_at' => $withdrawal->rejected_at?->toIso8601String(),
            'paid_at' => $withdrawal->paid_at?->toIso8601String(),
        ];
    }
}
