<?php

namespace App\Http\Controllers\Api\Backend;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\MerchantTransaction;
use App\Support\AdminDashboardData;
use Illuminate\Http\JsonResponse;

class WalletController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $merchants = Merchant::query()
            ->with('user')
            ->orderByDesc('total_platform_fee_paid')
            ->get();
        $recentFees = MerchantTransaction::query()
            ->with(['merchant.user', 'order'])
            ->where('type', 'platform_fee')
            ->latest('id')
            ->limit(8)
            ->get();
        $totalPlatformFees = (float) $merchants->sum('total_platform_fee_paid');
        $merchantsWithFees = $merchants->where('total_platform_fee_paid', '>', 0)->count();

        return response()->json([
            ...AdminDashboardData::walletPage(),
            'summary' => [
                'platform_fee_balance' => number_format($totalPlatformFees, 2, '.', ''),
                'merchants_charged' => $merchantsWithFees,
                'fee_transactions' => $recentFees->count(),
                'average_fee_per_merchant' => number_format($merchantsWithFees > 0 ? $totalPlatformFees / $merchantsWithFees : 0, 2, '.', ''),
            ],
            'top_merchants' => $merchants
                ->where('total_platform_fee_paid', '>', 0)
                ->take(8)
                ->map(fn (Merchant $merchant): array => [
                    'id' => $merchant->id,
                    'shop_name' => $merchant->shop_name,
                    'owner_name' => $merchant->user?->name,
                    'total_platform_fee_paid' => number_format((float) $merchant->total_platform_fee_paid, 2, '.', ''),
                    'available_balance' => number_format((float) $merchant->available_balance, 2, '.', ''),
                ])
                ->values()
                ->all(),
            'recent_fees' => $recentFees->map(fn (MerchantTransaction $transaction): array => [
                'id' => $transaction->id,
                'merchant_name' => $transaction->merchant?->shop_name ?? 'Unknown merchant',
                'order_number' => $transaction->order?->number,
                'amount' => number_format(abs((float) $transaction->amount), 2, '.', ''),
                'description' => $transaction->description,
                'created_at_label' => optional($transaction->created_at)?->format('d M Y, h:i A'),
            ])->values()->all(),
        ]);
    }
}
