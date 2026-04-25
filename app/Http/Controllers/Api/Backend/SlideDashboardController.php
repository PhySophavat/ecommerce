<?php

namespace App\Http\Controllers\Api\Backend;

use App\Http\Controllers\Controller;
use App\Support\AdminDashboardData;
use Illuminate\Http\JsonResponse;

class SlideDashboardController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(AdminDashboardData::slidesIndex());
    }
}
