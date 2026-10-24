<?php

namespace App\Services;

use App\Models\Flight;
use App\Models\Setting;
use App\Services\Flights\FlightOffer;

/**
 * Our selling price: the airline's fare in dollars plus the markup the admin set,
 * or the admin's fixed price for that flight.
 */
class Pricing
{
    /**
     * @return array{fare_cents: int, markup_cents: int, total_cents: int}|null Null when the fare's currency has no exchange rate.
     */
    public function quote(FlightOffer $offer, int $passengers, ?Flight $firstFlight = null): ?array
    {
        $rate = Setting::currencyRates()[$offer->currency] ?? null;

        if ($rate === null) {
            return null;
        }

        $fare = (int) round((float) $offer->amount * $rate * 100);

        if ($firstFlight?->price_override_cents !== null) {
            $total = $firstFlight->price_override_cents * $passengers;
        } else {
            $total = $fare
                + (int) round($fare * (float) Setting::get('markup_percent') / 100)
                + Setting::int('markup_fixed_cents') * $passengers;
        }

        return ['fare_cents' => $fare, 'markup_cents' => $total - $fare, 'total_cents' => $total];
    }
}
