<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('frontend.index', [
            'title' => 'Frontend | Storefront',
            'context' => [
                'app' => 'frontend',
            ],
        ]);
    }
}
