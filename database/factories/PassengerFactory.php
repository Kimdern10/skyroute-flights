<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Passenger;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Passenger>
 */
class PassengerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gender = fake()->randomElement(['m', 'f']);

        return [
            'booking_id' => Booking::factory(),
            'provider_passenger_id' => 'pas_'.fake()->regexify('[0-9A-Za-z]{22}'),
            'title' => $gender === 'm' ? 'mr' : 'ms',
            'given_name' => fake()->firstName($gender === 'm' ? 'male' : 'female'),
            'family_name' => fake()->lastName(),
            'gender' => $gender,
            'born_on' => fake()->dateTimeBetween('-70 years', '-18 years')->format('Y-m-d'),
            'ticket_number' => Booking::newTicketNumber(),
        ];
    }
}
