<?php

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\StorefrontData;
use Illuminate\Http\JsonResponse;

class HomeController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $users = User::query()
            ->latest()
            ->get();

        return response()->json([
            'meta' => [
                'brand' => 'Northstar Users',
                'headline' => 'Frontend user directory powered by Vue.',
                'subheadline' => 'Users are shown here while the main admin area now lives in the product dashboard.',
                'stack' => ['Laravel validation', 'Vue 3 frontend list', 'Admin products dashboard'],
                'stats' => [
                    ['value' => (string) $users->count(), 'label' => 'registered users'],
                    ['value' => $users->first()?->created_at?->toDateString() ?? 'N/A', 'label' => 'latest join'],
                ],
            ],
            'links' => [
                'frontend' => route('frontend.home'),
                'admin_users' => route('admin.products.index'),
            ],
            'users' => [
                'count' => $users->count(),
                'items' => $users
                    ->map(fn (User $user): array => StorefrontData::user($user))
                    ->values()
                    ->all(),
            ],
        ]);
    }
}
