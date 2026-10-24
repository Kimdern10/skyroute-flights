<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\TopUpStatus;
use App\Enums\WalletTransactionType;
use App\Models\Airport;
use App\Models\Booking;
use App\Models\Flight;
use App\Models\Passenger;
use App\Models\TopUpRequest;
use App\Models\User;
use App\Services\Wallet;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Airports always; sample customers, flights and bookings only on a local copy.
     */
    public function run(): void
    {
        $this->call(AirportSeeder::class);

        if (! app()->isLocal()) {
            return;
        }

        $wallet = app(Wallet::class);

        $admin = User::factory()->admin()->create([
            'name' => 'SkyRoute Admin',
            'email' => 'admin@skyroute.example',
        ]);

        $customers = collect([
            ['Ada Okafor', 'ada@example.com', 1200],
            ['James Carter', 'james@example.com', 450],
            ['Sofia Marin', 'sofia@example.com', 2400],
            ['Liam Chen', 'liam@example.com', 80],
            ['Grace Mensah', 'grace@example.com', 0],
        ])->map(function (array $row) use ($wallet, $admin): User {
            [$name, $email, $dollars] = $row;
            $user = User::factory()->create(['name' => $name, 'email' => $email]);

            if ($dollars > 0) {
                $wallet->change($user, $dollars * 100, WalletTransactionType::TopUp, 'Top-up TRF'.fake()->numerify('########'), by: $admin);
            }

            return $user;
        });

        // [from, to, flight number, days ahead, departure time, hours, seats sold of 20, fare]
        $flights = [
            ['LHR', 'JFK', '117', 5, '08:25', 8, 20, 42900],
            ['JFK', 'LHR', '118', 9, '19:40', 7, 6, 39800],
            ['LAX', 'NRT', '62', 12, '11:05', 12, 17, 88400],
            ['DXB', 'SIN', '432', 4, '02:50', 7, 3, 31200],
            ['LOS', 'LHR', '75', 7, '23:15', 7, 20, 61500],
            ['CDG', 'JFK', '22', 15, '13:30', 8, 9, 47300],
            ['SFO', 'CDG', '84', 20, '15:55', 11, 2, 72100],
            ['SYD', 'LAX', '11', 25, '10:00', 14, 12, 96800],
        ];

        foreach ($flights as $index => [$from, $to, $number, $days, $time, $hours, $sold, $fare]) {
            $departs = now()->addDays($days)->setTimeFromTimeString($time)->startOfMinute();

            $flight = Flight::query()->create([
                'carrier_code' => 'ZZ',
                'carrier_name' => 'Duffel Airways',
                'flight_number' => $number,
                'origin' => $from,
                'destination' => $to,
                'departs_at' => $departs,
                'arrives_at' => $departs->addHours($hours),
            ]);

            $remaining = $sold;

            while ($remaining > 0) {
                $customer = $customers[($index + $remaining) % $customers->count()];
                $seats = min($remaining, random_int(1, 3));
                $remaining -= $seats;
                $markup = (int) round($fare * $seats * 0.05) + 1500 * $seats;

                $booking = Booking::factory()->for($customer)->create([
                    'status' => BookingStatus::Confirmed,
                    'origin' => $from,
                    'destination' => $to,
                    'departs_at' => $flight->departs_at,
                    'arrives_at' => $flight->arrives_at,
                    'passengers_count' => $seats,
                    'segments' => [[
                        'carrier_code' => 'ZZ',
                        'carrier_name' => 'Duffel Airways',
                        'flight_number' => $number,
                        'origin' => $from,
                        'origin_name' => Airport::cityFor($from),
                        'destination' => $to,
                        'destination_name' => Airport::cityFor($to),
                        'departs_at' => $flight->departs_at->format('Y-m-d H:i:s'),
                        'arrives_at' => $flight->arrives_at->format('Y-m-d H:i:s'),
                        'aircraft' => ['Airbus A350', 'Boeing 787-9', 'Boeing 777-300ER'][$index % 3],
                    ]],
                    'fare_cents' => $fare * $seats,
                    'markup_cents' => $markup,
                    'total_cents' => $fare * $seats + $markup,
                    'provider_amount' => number_format($fare * $seats / 100, 2, '.', ''),
                    'contact_email' => $customer->email,
                ]);

                Passenger::factory()->count($seats)->for($booking)->create();
                $booking->flights()->attach($flight->id, ['seats' => $seats]);
                $flight->increment('seats_booked', $seats);
            }
        }

        TopUpRequest::factory()->for($customers[3])->create(['amount_cents' => 50000, 'note' => 'Sent from my savings account this morning.']);
        TopUpRequest::factory()->for($customers[4])->create(['amount_cents' => 150000]);
        TopUpRequest::factory()->for($customers[1])->create([
            'amount_cents' => 30000,
            'status' => TopUpStatus::Rejected,
            'admin_note' => 'We could not find this transfer. Please send the bank receipt to support.',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now()->subDay(),
        ]);
    }
}
