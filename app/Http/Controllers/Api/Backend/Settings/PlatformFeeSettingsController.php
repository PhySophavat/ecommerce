<?php

namespace App\Http\Controllers\Api\Backend\Settings;

use App\Http\Controllers\Controller;
use App\Models\PlatformFeeSetting;
use App\Services\PlatformFeeService;
use App\Support\AdminDashboardData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlatformFeeSettingsController extends Controller
{
    public function show(PlatformFeeService $platformFeeService): JsonResponse
    {
        return response()->json($this->payload(PlatformFeeSetting::current(), $platformFeeService));
    }

    public function update(Request $request, PlatformFeeService $platformFeeService): JsonResponse
    {
        $validated = $request->validate([
            'is_enabled' => ['required', 'boolean'],
            'fee_type' => ['required', Rule::in(['percentage'])],
            'fee_value' => ['required', 'numeric', 'min:0'],
            'apply_stage' => ['required', Rule::in(['payment_success', 'order_completed'])],
            'deduct_from' => ['required', Rule::in(['merchant_balance'])],
        ]);

        if ($validated['fee_type'] === 'percentage' && (float) $validated['fee_value'] > 100) {
            return response()->json([
                'message' => 'Percentage platform fee cannot be greater than 100.',
                'errors' => [
                    'fee_value' => ['Percentage platform fee cannot be greater than 100.'],
                ],
            ], 422);
        }

        $setting = PlatformFeeSetting::query()->latest('id')->first() ?? new PlatformFeeSetting();
        $setting->fill([
            'is_enabled' => (bool) $validated['is_enabled'],
            'fee_type' => 'percentage',
            'fee_value' => round((float) $validated['fee_value'], 2),
            'apply_stage' => $validated['apply_stage'],
            'deduct_from' => $validated['deduct_from'],
        ]);
        $setting->save();

        return response()->json([
            ...$this->payload($setting->fresh(), $platformFeeService),
            'message' => 'Platform fee settings saved successfully.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(PlatformFeeSetting $setting, PlatformFeeService $platformFeeService): array
    {
        return [
            ...AdminDashboardData::platformFeeSettings(),
            'setting' => [
                'is_enabled' => (bool) $setting->is_enabled,
                'fee_type' => 'percentage',
                'fee_value' => number_format((float) $setting->fee_value, 2, '.', ''),
                'apply_stage' => $setting->apply_stage,
                'deduct_from' => $setting->deduct_from,
            ],
            'preview' => $platformFeeService->preview($setting),
        ];
    }
}
