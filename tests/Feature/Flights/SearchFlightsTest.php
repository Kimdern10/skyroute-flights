<?php

use App\Models\Flight;
use App\Models\Setting;
use Database\Seeders\AirportSeeder;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function searchPage(array $query = [])
{
    return Livewire::withQueryParams(array_merge(['from' => 'LHR', 'to' => 'JFK', 'date' => '2026-11-20'], $query))
        ->test('pages::flights.index')
        ->call('load');
}

function savedFlight(array $attributes = []): Flight
{
    return Flight::factory()->create(array_merge([
        'carrier_code' => 'ZZ',
        'flight_number' => '117',
        'departs_at' => '2026-11-20 08:25:00',
        'arrives_at' => '2026-11-20 11:25:00',
    ], $attributes));
}

test('the home page shows the search form and popular routes', function () {
    $this->seed(AirportSeeder::class);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Search flights')
        ->assertSee('Trending routes')
        ->assertSee('London');
});

test('the results page lists flights with our price', function () {
    fakeDuffel();

    // $400 fare + 5% markup + $15 service fee
    searchPage()
        ->assertSee('Duffel Airways')
        ->assertSee('ZZ 117')
        ->assertSee('$435.00')
        ->assertSee('Book now')
        ->assertDontSee('Fully booked');
});

test('a sold-out flight shows as fully booked and cannot be booked', function () {
    fakeDuffel();
    savedFlight(['seats_total' => 12, 'seats_booked' => 12]);

    searchPage()
        ->assertSee('Fully booked')
        ->assertSee('No seats available')
        ->assertDontSee(route('flights.book', 'off_0000AgHGicvyjqDEO1qxfh'));
});

test('a flight with too few seats for the group cannot be booked', function () {
    fakeDuffel([duffelOffer(passengers: 3)]);
    savedFlight(['seats_total' => 10, 'seats_booked' => 8]);

    searchPage(['passengers' => 3])
        ->assertSee('Only 2 seats left')
        ->assertSee('Not enough seats');
});

test('the admin fixed price replaces the fare plus markup', function () {
    fakeDuffel();
    savedFlight(['price_override_cents' => 29900]);

    searchPage()->assertSee('$299.00')->assertDontSee('$435.00');
});

test('fares in other currencies are converted to dollars', function () {
    fakeDuffel([duffelOffer(['total_amount' => '100.00', 'total_currency' => 'GBP'])]);
    Setting::put(['currency_rates' => 'GBP=1.50', 'markup_percent' => '0', 'markup_fixed_cents' => '0']);

    searchPage()->assertSee('$150.00');
});

test('fares in a currency without a rate are hidden', function () {
    fakeDuffel([duffelOffer(['total_currency' => 'JPY'])]);

    searchPage()->assertSee('No flights on this day');
});

test('direct only hides flights with stops', function () {
    $connecting = duffelOffer(['id' => 'off_connecting']);
    $connecting['slices'][0]['segments'][] = array_replace($connecting['slices'][0]['segments'][0], [
        'marketing_carrier_flight_number' => '900',
        'departing_at' => '2026-11-20T13:00:00',
    ]);
    fakeDuffel([duffelOffer(), $connecting]);

    searchPage(['direct' => true])->assertSee('ZZ 117')->assertDontSee('ZZ 900');
});

test('without an API key the page says search is not connected', function () {
    config(['services.duffel.token' => null]);
    Http::preventStrayRequests();

    searchPage()->assertSee('Flight search is not connected yet');
});

test('a city name is turned into its airport code', function () {
    $this->seed(AirportSeeder::class);
    fakeDuffel();

    searchPage(['from' => 'london', 'to' => 'New York'])
        ->assertSet('from', 'LHR')
        ->assertSet('to', 'JFK');
});
