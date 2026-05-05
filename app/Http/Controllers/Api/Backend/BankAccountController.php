<?php

namespace App\Http\Controllers\Api\Backend;

use App\Http\Controllers\Controller;
use App\Models\MerchantBankAccount;
use App\Services\MerchantBankAccountService;
use App\Support\AdminDashboardData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function __construct(private readonly MerchantBankAccountService $bankAccountService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $status = (string) $request->query('status', 'all');

        $query = MerchantBankAccount::query()
            ->with('merchant.user')
            ->latest();

        if ($status !== '' && $status !== 'all') {
            $query->where('status', $status);
        }

        $accounts = $query->get();

        return response()->json([
            ...AdminDashboardData::bankAccountsPage(),
            'summary' => [
                'all' => MerchantBankAccount::query()->count(),
                'pending' => MerchantBankAccount::query()->where('status', 'pending')->count(),
                'approved' => MerchantBankAccount::query()->where('status', 'approved')->count(),
                'rejected' => MerchantBankAccount::query()->where('status', 'rejected')->count(),
                'disabled' => MerchantBankAccount::query()->where('status', 'disabled')->count(),
            ],
            'filters' => [
                ['label' => 'All', 'value' => 'all'],
                ['label' => 'Pending', 'value' => 'pending'],
                ['label' => 'Approved', 'value' => 'approved'],
                ['label' => 'Rejected', 'value' => 'rejected'],
                ['label' => 'Disabled', 'value' => 'disabled'],
            ],
            'selected_status' => $status,
            'accounts' => $accounts->map(fn (MerchantBankAccount $account): array => $this->bankAccountService->payload($account, true))->all(),
        ]);
    }

    public function approve(MerchantBankAccount $bankAccount): JsonResponse
    {
        $account = $this->bankAccountService->approve($bankAccount);

        return response()->json([
            'message' => 'Bank account approved successfully.',
            'account' => $this->bankAccountService->payload($account, true),
        ]);
    }

    public function reject(Request $request, MerchantBankAccount $bankAccount): JsonResponse
    {
        $validated = $request->validate([
            'reject_reason' => ['nullable', 'string'],
        ]);

        $account = $this->bankAccountService->reject($bankAccount, $validated['reject_reason'] ?? null);

        return response()->json([
            'message' => 'Bank account rejected successfully.',
            'account' => $this->bankAccountService->payload($account, true),
        ]);
    }

    public function disable(Request $request, MerchantBankAccount $bankAccount): JsonResponse
    {
        $validated = $request->validate([
            'reject_reason' => ['nullable', 'string'],
        ]);

        $account = $this->bankAccountService->disable($bankAccount, $validated['reject_reason'] ?? null);

        return response()->json([
            'message' => 'Bank account disabled successfully.',
            'account' => $this->bankAccountService->payload($account, true),
        ]);
    }

    public function destroy(MerchantBankAccount $bankAccount): JsonResponse
    {
        $this->bankAccountService->deleteForAdmin($bankAccount);

        return response()->json([
            'message' => 'Bank account deleted successfully.',
        ]);
    }
}
