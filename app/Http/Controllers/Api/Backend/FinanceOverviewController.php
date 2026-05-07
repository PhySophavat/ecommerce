<?php

namespace App\Http\Controllers\Api\Backend;

use App\Http\Controllers\Controller;
use App\Services\FinanceReportingService;
use App\Support\AdminDashboardData;
use Illuminate\Http\JsonResponse;

class FinanceOverviewController extends Controller
{
    public function __construct(private readonly FinanceReportingService $financeReportingService)
    {
    }

    public function __invoke(): JsonResponse
    {
        $this->financeReportingService->rebuildSnapshots();

        return response()->json([
            ...AdminDashboardData::financeOverviewPage(),
            ...$this->financeReportingService->overview(),
            'scope' => 'admin',
        ]);
    }
}
