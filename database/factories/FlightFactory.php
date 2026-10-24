<?php

namespace Database\Factories;

use App\Models\Flight;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Flight>
 */
class FlightFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $departs = fake()->dateTimeBetween('+2 days', '+60 days');
        $departs->setTime((int) $departs->format('H'), [0, 15, 30, 45][random_int(0, 3)]);

        return [
            'carrier_code' => 'ZZ',
            'carrier_name' => 'Duffel Airways',
            'flight_number' => (string) fake()->unique()->numberBetween(100, 9999),
            'origin' => 'LHR',
            'destination' => 'JFK',
            'departs_at' => $departs,
            'arrives_at' => (clone $departs)->modify('+8 hours'),
            'seats_total' => null,
            'seats_booked' => 0,
        ];
    }

    public function full(): static
    {
        return $this->state(fn (): array => ['seats_total' => 10, 'seats_booked' => 10]);
    }
}
