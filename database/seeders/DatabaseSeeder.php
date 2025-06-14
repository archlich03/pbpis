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
            'name' => 'Rokas Stankūnas',
            'role' => 'IT administratorius',
            'email' => 'admin@knf.vu.lt',
            'password' => bcrypt('admin123'),
            'gender' => 1,
        ]);

        User::factory()->create([
            'name' => 'Meduolis Šaunuolis',
            'role' => 'Sekretorius',
            'email' => 'meduolis.saunuolis@knf.vu.lt',
            'password' => bcrypt('sekre123'),
            'gender' => 1,
        ]);

        User::factory()->create([
            'name' => 'Umėdė Garduolė',
            'role' => 'Balsuojantysis',
            'email' => 'umede.garduole@knf.vu.lt',
            'password' => bcrypt('balsa123'),
            'gender' => 0,
            'pedagogical_name' => "lekt.",
        ]);
    }
}

