<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

class StorefrontData
{
    public static function user(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name ?: Str::before($user->email, '@'),
            'email' => $user->email,
            'joined_at' => $user->created_at?->toDateString(),
        ];
    }
}
