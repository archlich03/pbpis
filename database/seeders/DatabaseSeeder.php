<?php

namespace Database\Seeders;

use App\Models\Vartotojai;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Vartotojai::factory()->create([
            'ms_id' => '',
            'password' => bcrypt('admin123'),
            'vardas' => 'IT administratorius',
            'pavarde' => 'Administratorius',
            'el_pastas' => 'admin@knf.vu.lt',
            'role' => 'IT administratorius',
            'pedagoginis_vardas' => 'Doc., dr.',
            'lytis' => true,
            'prisijungimo_statusas' => false,
            'paskutinis_prisijungimas' => now(),
        ]);

        Vartotojai::factory()->create([
            'ms_id' => '',
            'password' => bcrypt('sekre123'),
            'vardas' => 'Sekretorius',
            'pavarde' => 'Sekretorius',
            'el_pastas' => 'meduolis.saunuolis@knf.vu.lt',
            'role' => 'Sekretorius',
            'pedagoginis_vardas' => 'Doc., dr.',
            'lytis' => true,
            'prisijungimo_statusas' => false,
            'paskutinis_prisijungimas' => now(),
        ]);

        Vartotojai::factory()->create([
            'ms_id' => '',
            'password' => bcrypt('balsa123'),
            'vardas' => 'Balsuojantysis',
            'pavarde' => 'Balsuojantysis',
            'el_pastas' => 'umede.garduole@knf.vu.lt',
            'role' => 'Balsuojantysis',
            'pedagoginis_vardas' => 'Doc., dr.',
            'lytis' => true,
            'prisijungimo_statusas' => false,
            'paskutinis_prisijungimas' => now(),
        ]);
    }
}

