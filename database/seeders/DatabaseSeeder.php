<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        collect([
            [
                'name' => 'Jamie Carter',
                'email' => 'jamie@example.com',
            ],
            [
                'name' => 'Morgan Lee',
                'email' => 'morgan@example.com',
            ],
            [
                'name' => 'Avery Kim',
                'email' => 'avery@example.com',
            ],
        ])->each(function (array $user): void {
            User::query()->updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => 'password123',
                    'email_verified_at' => now(),
                ],
            );
        });
    }
}
