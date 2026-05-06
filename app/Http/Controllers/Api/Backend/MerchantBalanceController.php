<?php

namespace App\Http\Controllers\Api\Backend;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Support\AdminDashboardData;
use Illuminate\Http\JsonResponse;

class MerchantBalanceController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $merchants = Merchant::query()
            ->with(['user', 'bankAccounts'])
            ->orderByDesc('balance_total')
            ->orderBy('shop_name')
            ->get();

        return response()->json([
            ...AdminDashboardData::merchantBalancePage(),
            'summary' => [
                'merchants' => $merchants->count(),
                'balance_total' => number_format((float) $merchants->sum('balance_total'), 2, '.', ''),
                'available_balance' => number_format((float) $merchants->sum('available_balance'), 2, '.', ''),
                'pending_balance' => number_format((float) $merchants->sum('pending_balance'), 2, '.', ''),
                'total_deposited' => number_format((float) $merchants->sum('total_deposited'), 2, '.', ''),
                'total_withdrawn' => number_format((float) $merchants->sum('total_withdrawn'), 2, '.', ''),
            ],
            'merchants' => $merchants->map(fn (Merchant $merchant): array => [
                'id' => $merchant->id,
                'user_id' => $merchant->user_id,
                'shop_name' => $merchant->shop_name,
                'owner_name' => $merchant->user?->name,
                'email' => $merchant->user?->email,
                'status' => $merchant->status,
                'bank_accounts' => $merchant->bankAccounts->map(fn ($account): array => [
                    'id' => $account->id,
                    'bank_name' => $account->bank_name,
                    'account_holder_name' => $account->account_holder_name,
                    'account_number' => $account->maskedAccountNumber(),
                    'currency' => $account->currency,
                    'status' => $account->status,
                ])->values()->all(),
                'balance_total' => number_format((float) $merchant->balance_total, 2, '.', ''),
                'available_balance' => number_format((float) $merchant->available_balance, 2, '.', ''),
                'pending_balance' => number_format((float) $merchant->pending_balance, 2, '.', ''),
                'total_deposited' => number_format((float) $merchant->total_deposited, 2, '.', ''),
                'total_withdrawn' => number_format((float) $merchant->total_withdrawn, 2, '.', ''),
                'total_platform_fee_paid' => number_format((float) $merchant->total_platform_fee_paid, 2, '.', ''),
            ])->values()->all(),
        ]);
    }
}
