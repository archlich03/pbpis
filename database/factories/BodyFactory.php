<?php

namespace Database\Factories;

use App\Models\bodies;
use Illuminate\Database\Eloquent\Factories\Factory;

class DariniaiFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = bodies::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'pavadinimas' => $this->faker->words(3, true),
            'klasifikacija' => 'SPK',
            'pirmininko_id' => rand(1, 10),
            'nariai' => json_encode(array_map(function () {
                return rand(1, 10);
            }, range(1, rand(1, 10)))),
            'ar_bakalauro_sp' => true,
        ];
    }
}
