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
     */
    public function run(): void
    {
        // Use firstOrCreate to avoid duplicate key errors
        User::firstOrCreate(
            ['email' => env('DEFAULT_EMAIL', 'admin@knf.vu.lt')],
            [
                'name' => 'IT admin',
                'role' => 'IT administratorius',
                'password' => Hash::make(env('DEFAULT_PASSWORD', 'admin123')),
                'gender' => 1,
            ]
        );
    }
}

