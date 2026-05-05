<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\MerchantBankAccount;
use App\Services\MerchantBankAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function __construct(private readonly MerchantBankAccountService $bankAccountService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $merchant = $this->merchant($request);
        $accounts = $merchant->bankAccounts()->orderByDesc('is_default')->orderByDesc('id')->get();

        return response()->json([
            'accounts' => $accounts->map(fn (MerchantBankAccount $account): array => $this->bankAccountService->payload($account))->all(),
            'meta' => $this->bankAccountService->metadata(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $merchant = $this->merchant($request);
        $validated = $request->validate($this->bankAccountService->merchantRules());

        $account = $this->bankAccountService->createForMerchant(
            $merchant,
            $validated,
            $request->file('qr_image'),
        );

        return response()->json([
            'message' => 'Bank account submitted for admin approval.',
            'account' => $this->bankAccountService->payload($account),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $merchant = $this->merchant($request);
        $account = $merchant->bankAccounts()->findOrFail($id);
        $validated = $request->validate($this->bankAccountService->merchantRules(true));

        $account = $this->bankAccountService->updateForMerchant(
            $account,
            $validated,
            $request->file('qr_image'),
        );

        return response()->json([
            'message' => $account->isApproved()
                ? 'Default bank account updated successfully.'
                : 'Bank account saved and submitted for admin approval.',
            'account' => $this->bankAccountService->payload($account),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $merchant = $this->merchant($request);
        $account = $merchant->bankAccounts()->findOrFail($id);

        $this->bankAccountService->deleteForMerchant($account);

        return response()->json([
            'message' => 'Bank account deleted successfully.',
        ]);
    }

    private function merchant(Request $request): Merchant
    {
        return $request->user()->merchant()->firstOrFail();
    }
}
