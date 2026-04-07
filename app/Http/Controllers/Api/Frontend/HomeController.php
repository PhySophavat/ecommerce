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
                'subheadline' => 'Laravel handles user creation in Blade. Vue is used only to show users on the frontend.',
                'stack' => ['Laravel user form', 'Vue 3 frontend list'],
                'stats' => [
                    ['value' => (string) $users->count(), 'label' => 'registered users'],
                    ['value' => $users->first()?->created_at?->toDateString() ?? 'N/A', 'label' => 'latest join'],
                ],
            ],
            'links' => [
                'frontend' => route('frontend.home'),
                'admin_users' => route('admin.users.create'),
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
