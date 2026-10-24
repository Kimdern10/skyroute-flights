<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * A Duffel offer as the API returns it: one London → New York flight for $400.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function duffelOffer(array $overrides = [], int $passengers = 1): array
{
    return array_replace_recursive([
        'id' => 'off_0000AgHGicvyjqDEO1qxfh',
        'total_amount' => '400.00',
        'total_currency' => 'USD',
        'expires_at' => now()->addMinutes(30)->toIso8601String(),
        'owner' => ['name' => 'Duffel Airways', 'iata_code' => 'ZZ', 'logo_symbol_url' => null],
        'passengers' => array_map(fn (int $index): array => ['id' => 'pas_'.$index, 'type' => 'adult'], range(1, $passengers)),
        'slices' => [[
            'duration' => 'PT8H',
            'segments' => [[
                'departing_at' => '2026-11-20T08:25:00',
                'arriving_at' => '2026-11-20T11:25:00',
                'origin' => ['iata_code' => 'LHR', 'name' => 'Heathrow', 'city_name' => 'London'],
                'destination' => ['iata_code' => 'JFK', 'name' => 'John F. Kennedy', 'city_name' => 'New York'],
                'marketing_carrier' => ['iata_code' => 'ZZ', 'name' => 'Duffel Airways'],
                'marketing_carrier_flight_number' => '117',
                'aircraft' => ['name' => 'Airbus A350'],
                'passengers' => [['cabin_class_marketing_name' => 'Economy']],
            ]],
        ]],
    ], $overrides);
}

/**
 * Point the app at a fake Duffel that returns these offers, and confirms orders.
 *
 * @param  list<array<string, mixed>>  $offers
 */
function fakeDuffel(array $offers = [], ?array $order = null, int $orderStatus = 201): void
{
    config(['services.duffel.token' => 'duffel_test_token']);

    $offers = $offers ?: [duffelOffer()];

    Http::preventStrayRequests();
    Http::fake([
        'api.duffel.com/air/offer_requests*' => Http::response(['data' => ['offers' => $offers]]),
        'api.duffel.com/air/offers/*' => Http::response(['data' => $offers[0]]),
        'api.duffel.com/air/orders' => Http::response($order ?? ['data' => [
            'id' => 'ord_0000AgHGicvyjqDEO1qxfh',
            'booking_reference' => 'RZPNX8',
            'documents' => array_map(fn (array $passenger): array => [
                'type' => 'electronic_ticket',
                'unique_identifier' => '125210631281'.substr($passenger['id'], -1),
                'passenger_ids' => [$passenger['id']],
            ], $offers[0]['passengers']),
        ]], $orderStatus),
    ]);
}

/**
 * Passenger details as typed on the booking form.
 *
 * @return array{title: string, given_name: string, family_name: string, gender: string, born_on: string}
 */
function passengerDetails(string $familyName = 'Okafor'): array
{
    return ['title' => 'ms', 'given_name' => 'Ada', 'family_name' => $familyName, 'gender' => 'f', 'born_on' => '1990-04-12'];
}
