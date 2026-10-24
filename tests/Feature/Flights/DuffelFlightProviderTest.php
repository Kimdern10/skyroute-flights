<?php

use App\Models\Setting;
use App\Services\Flights\DuffelFlightProvider;
use App\Services\Flights\FlightProviderException;
use App\Services\Flights\FlightSearch;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

test('search sends the trip to Duffel and reads the offers', function () {
    fakeDuffel([duffelOffer(passengers: 2)]);

    $offers = app(DuffelFlightProvider::class)->search(new FlightSearch('LHR', 'JFK', '2026-11-20', 2));

    Http::assertSent(fn (Request $request) => str_contains($request->url(), '/air/offer_requests')
        && $request->hasHeader('Authorization', 'Bearer duffel_test_token')
        && $request->hasHeader('Duffel-Version', 'v2')
        && $request['data']['slices'][0] === ['origin' => 'LHR', 'destination' => 'JFK', 'departure_date' => '2026-11-20']
        && count($request['data']['passengers']) === 2);

    expect($offers)->toHaveCount(1);

    $offer = $offers->first();
    expect($offer->amount)->toBe('400.00')
        ->and($offer->airlineName)->toBe('Duffel Airways')
        ->and($offer->passengerIds)->toBe(['pas_1', 'pas_2'])
        ->and($offer->durationMinutes)->toBe(480)
        ->and($offer->first()->flightNumber)->toBe('117')
        ->and($offer->first()->departsAt->format('Y-m-d H:i'))->toBe('2026-11-20 08:25');
});

test('repeat fares for the same flights keep only the cheapest', function () {
    fakeDuffel([
        duffelOffer(['id' => 'off_flex', 'total_amount' => '650.00']),
        duffelOffer(['id' => 'off_basic', 'total_amount' => '400.00']),
    ]);

    $offers = app(DuffelFlightProvider::class)->search(new FlightSearch('LHR', 'JFK', '2026-11-20'));

    expect($offers->pluck('id')->all())->toBe(['off_basic']);
});

test('the key saved in the admin is used before the one in .env', function () {
    fakeDuffel();
    Setting::put(['duffel_token' => 'duffel_test_from_admin']);

    app(DuffelFlightProvider::class)->search(new FlightSearch('LHR', 'JFK', '2026-11-20'));

    Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer duffel_test_from_admin'));
});

test('searching without a key explains that search is not connected', function () {
    config(['services.duffel.token' => null]);
    Http::preventStrayRequests();

    app(DuffelFlightProvider::class)->search(new FlightSearch('LHR', 'JFK', '2026-11-20'));
})->throws(FlightProviderException::class, 'Flight search is not connected yet.');

test('a rejected key gives a clear message', function () {
    config(['services.duffel.token' => 'duffel_test_wrong']);
    Http::fake(['api.duffel.com/*' => Http::response(['errors' => [['message' => 'Unauthorized']]], 401)]);

    app(DuffelFlightProvider::class)->search(new FlightSearch('LHR', 'JFK', '2026-11-20'));
})->throws(FlightProviderException::class, 'The flight API key is not valid.');

test('an expired offer is reported as missing', function () {
    config(['services.duffel.token' => 'duffel_test_token']);
    Http::fake(['api.duffel.com/air/offers/*' => Http::response(['errors' => [['code' => 'not_found']]], 404)]);

    expect(app(DuffelFlightProvider::class)->find('off_gone'))->toBeNull();
});
