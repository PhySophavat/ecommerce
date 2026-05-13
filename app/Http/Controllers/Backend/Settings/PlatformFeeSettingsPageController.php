<?php

namespace App\Http\Controllers\Backend\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class PlatformFeeSettingsPageController extends Controller
{
    public function __invoke(): View
    {
        return view('backend.products.index', [
            'title' => 'Admin | Platform Fee Settings',
            'context' => [
                'app' => 'backend-platform-fee-settings',
                'screen' => 'platform-fee-settings',
                'role_scope' => 'admin',
                'endpoint' => route('api.admin.platform-fee-settings.show'),
            ],
        ]);
    }
}
