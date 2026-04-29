<?php

namespace App\Http\Controllers\Api\Backend;

use App\Http\Controllers\Controller;
use App\Models\MerchantDeposit;
use App\Models\Withdrawal;
use App\Support\AdminDashboardData;
use Illuminate\Http\JsonResponse;

class WalletController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            ...AdminDashboardData::walletPage(),
            'summary' => [
                'pending_deposits' => MerchantDeposit::query()->where('status', 'pending')->count(),
                'approved_deposits' => MerchantDeposit::query()->where('status', 'approved')->count(),
                'pending_withdrawals' => Withdrawal::query()->where('status', 'pending')->count(),
                'paid_withdrawals' => Withdrawal::query()->where('status', 'paid')->count(),
            ],
            'links' => [
                'deposits' => route('admin.deposits.index'),
                'withdrawals' => route('admin.withdrawals.index'),
            ],
        ]);
    }
}
