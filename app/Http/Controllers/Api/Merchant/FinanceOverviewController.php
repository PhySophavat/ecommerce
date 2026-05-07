<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use App\Services\FinanceReportingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceOverviewController extends Controller
{
    public function __construct(private readonly FinanceReportingService $financeReportingService)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $merchant = $request->user()->merchant()->firstOrFail();

        $this->financeReportingService->rebuildSnapshots($merchant);

        return response()->json([
            ...$this->financeReportingService->overview($merchant),
            'scope' => 'merchant',
            'dashboard' => [
                'meta' => [
                    'brand' => 'E-commerce',
                    'page_title' => 'Finance Overview',
                    'kicker' => 'Merchant finance',
                    'subheadline' => 'Track your balance, payment performance, and order outcomes from one dashboard.',
                ],
            ],
        ]);
    }
}
