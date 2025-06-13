<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'ms_id' => '',
            'password' => bcrypt('admin123'),
            'name' => 'IT administratorius',
            'email' => 'admin@knf.vu.lt',
            'role' => 'IT administratorius',
            'pedagoginis_vardas' => 'Doc., dr.',
            'lytis' => true,
            'prisijungimo_statusas' => false,
            'paskutinis_prisijungimas' => now(),
        ]);

        User::factory()->create([
            'ms_id' => '',
            'password' => bcrypt('sekre123'),
            'name' => 'Meduolis Šaunuolis',
            'email' => 'meduolis.saunuolis@knf.vu.lt',
            'role' => 'Sekretorius',
            'pedagoginis_vardas' => 'Doc., dr.',
            'lytis' => true,
            'prisijungimo_statusas' => false,
            'paskutinis_prisijungimas' => now(),
        ]);

        User::factory()->create([
            'ms_id' => '',
            'password' => bcrypt('balsa123'),
            'name' => 'Ūmėdė Garduolė',
            'email' => 'umede.garduole@knf.vu.lt',
            'role' => 'Balsuojantysis',
            'pedagoginis_vardas' => 'Doc., dr.',
            'lytis' => true,
            'prisijungimo_statusas' => false,
            'paskutinis_prisijungimas' => now(),
        ]);
    }
}

