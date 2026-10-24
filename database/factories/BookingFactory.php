<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $departs = now()->addDays(fake()->numberBetween(3, 50))->setTime(fake()->numberBetween(6, 22), 15);
        $fare = fake()->numberBetween(15000, 90000);
        $markup = (int) round($fare * 0.05) + 1500;

        return [
            'user_id' => User::factory(),
            'reference' => Booking::newReference(),
            'status' => BookingStatus::Confirmed,
            'offer_id' => 'off_'.fake()->regexify('[0-9A-Za-z]{22}'),
            'provider_order_id' => 'ord_'.fake()->regexify('[0-9A-Za-z]{22}'),
            'airline_reference' => fake()->regexify('[A-Z0-9]{6}'),
            'carrier_code' => 'ZZ',
            'carrier_name' => 'Duffel Airways',
            'origin' => 'LHR',
            'destination' => 'JFK',
            'departs_at' => $departs,
            'arrives_at' => $departs->addHours(8),
            'cabin' => 'Economy',
            'passengers_count' => 1,
            'segments' => [[
                'carrier_code' => 'ZZ',
                'carrier_name' => 'Duffel Airways',
                'flight_number' => (string) fake()->numberBetween(100, 999),
                'origin' => 'LHR',
                'origin_name' => 'London',
                'destination' => 'JFK',
                'destination_name' => 'New York',
                'departs_at' => $departs->format('Y-m-d H:i:s'),
                'arrives_at' => $departs->addHours(8)->format('Y-m-d H:i:s'),
                'aircraft' => 'Airbus A350',
            ]],
            'fare_cents' => $fare,
            'markup_cents' => $markup,
            'total_cents' => $fare + $markup,
            'provider_amount' => number_format($fare / 100, 2, '.', ''),
            'provider_currency' => 'USD',
            'contact_email' => fake()->safeEmail(),
            'contact_phone' => '+14155550123',
        ];
    }
}
