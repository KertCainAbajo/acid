<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@example.com')],
            [
                'name' => env('ADMIN_NAME', 'Acid Reflux Administrator'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'change-me-before-production')),
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
            ],
        );
    }
}
