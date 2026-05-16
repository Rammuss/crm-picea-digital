<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        User::updateOrCreate(
            ['email' => env('ADMIN_SEED_EMAIL', 'admin@local.test')],
            [
                'name' => env('ADMIN_SEED_NAME', 'admin'),
                'password' => Hash::make(env('ADMIN_SEED_PASSWORD', 'ChangeMe_12345!')),
                'role' => 'admin',
                'is_active' => true,
                'google_auth_enabled' => false,
            ]
        );
    }
}
