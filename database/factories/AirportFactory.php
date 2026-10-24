<?php

namespace Database\Factories;

use App\Models\Airport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Airport>
 */
class AirportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'iata_code' => fake()->unique()->regexify('[A-Z]{3}'),
            'name' => fake()->city().' International',
            'city' => fake()->city(),
            'country' => fake()->country(),
        ];
    }
}
