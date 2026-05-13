<?php

namespace App\Http\Controllers\Backend\Slide;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class SlideController extends Controller
{
    public function index(): View
    {
        return view('backend.products.index', [
            'title' => 'Admin | Slides',
            'context' => [
                'app' => 'backend-slides',
                'screen' => 'sliders',
                'role_scope' => 'admin',
            ],
        ]);
    }
}
