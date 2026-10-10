<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Create the first administrator from the ADMIN_* environment values.
     *
     * Once an administrator exists, their name, email and password are managed from
     * Account settings, so this seeder never overwrites them. Use
     * `php artisan admin:reset-password` to recover a locked-out admin account.
     */
    public function run(): void
    {
        if (User::where('role', User::ROLE_ADMIN)->exists()) {
            $this->command?->info('An administrator already exists. Manage it from Account settings.');

            return;
        }

        User::create([
            'email' => env('ADMIN_EMAIL', 'admin@example.com'),
            'name' => env('ADMIN_NAME', 'Acid Reflux Administrator'),
            'password' => Hash::make(env('ADMIN_PASSWORD', 'change-me-before-production')),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }
}
