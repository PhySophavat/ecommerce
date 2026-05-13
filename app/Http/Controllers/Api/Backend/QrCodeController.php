<?php

namespace App\Http\Controllers\Api\Backend;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\MerchantDeposit;
use App\Models\Withdrawal;
use App\Support\AdminDashboardData;
use Illuminate\Http\JsonResponse;

class QrCodeController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $previewMerchant = Merchant::query()->latest('id')->first();

        return response()->json([
            ...AdminDashboardData::qrCodesPage(),
            'summary' => [
                'pending_deposits' => MerchantDeposit::query()->where('status', 'pending')->count(),
                'approved_deposits' => MerchantDeposit::query()->where('status', 'approved')->count(),
                'pending_withdrawals' => Withdrawal::query()->where('status', 'pending')->count(),
                'paid_withdrawals' => Withdrawal::query()->where('status', 'paid')->count(),
            ],
            'deposit_preview' => [
                'merchant' => [
                    'shop_name' => $previewMerchant?->shop_name ?? 'Merchant Shop',
                ],
                'providers' => array_values(config('merchant_wallet.providers', [])),
            ],
            'links' => [
                'deposits' => route('admin.deposits.index'),
                'withdrawals' => route('admin.withdrawals.index'),
            ],
        ]);
    }
}
