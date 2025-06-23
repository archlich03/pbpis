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
        User::factory()->create([
            'name' => 'IT admin',
            'role' => 'IT admin',
            'email' => env('DEFAULT_EMAIL', 'admin@knf.vu.lt'),
            'password' => Hash::make(env('DEFAULT_PASSWORD', 'admin123')),
            'gender' => 1,
        ]);
    }
}

