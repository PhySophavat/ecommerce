<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class SlideManagementController extends Controller
{
    public function index(): View
    {
        return view('backend.products.index', [
            'title' => 'Admin | Slides',
            'context' => [
                'app' => 'backend-slides',
                'screen' => 'sliders',
            ],
        ]);
    }
}
