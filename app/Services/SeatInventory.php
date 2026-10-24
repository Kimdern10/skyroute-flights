<?php

namespace App\Services;

use App\Models\Flight;
use App\Models\Setting;
use App\Services\Flights\FlightOffer;
use App\Services\Flights\FlightSegment;
use Illuminate\Support\Collection;

/**
 * Seats we still have to sell on the flights the airline API returns.
 */
class SeatInventory
{
    /**
     * Our saved flights for these offers, keyed by journey segment.
     *
     * @param  iterable<FlightOffer>  $offers
     * @return Collection<string, Flight>
     */
    public function flightsFor(iterable $offers): Collection
    {
        $segments = collect($offers)->flatMap(fn (FlightOffer $offer): array => $offer->segments);

        if ($segments->isEmpty()) {
            return collect();
        }

        return Flight::query()
            ->whereIn('departs_at', $segments->map(fn (FlightSegment $segment): string => $segment->departsAt->format('Y-m-d H:i:s'))->unique()->values())
            ->get()
            ->keyBy(fn (Flight $flight): string => self::key($flight->carrier_code, $flight->flight_number, $flight->departs_at->format('Y-m-d H:i:s')));
    }

    /**
     * The fewest seats left on any flight of the journey.
     *
     * @param  Collection<string, Flight>  $flights
     */
    public function seatsLeft(FlightOffer $offer, Collection $flights): int
    {
        return collect($offer->segments)
            ->map(fn (FlightSegment $segment): int => $flights->get(self::keyFor($segment))?->seatsLeft() ?? Setting::int('seats_per_flight'))
            ->min();
    }

    /**
     * Make sure each flight of the journey is saved, so its seats can be counted and locked.
     *
     * @return list<Flight>
     */
    public function ensureFlights(FlightOffer $offer): array
    {
        return array_map(fn (FlightSegment $segment): Flight => Flight::query()->createOrFirst(
            [
                'carrier_code' => $segment->carrierCode,
                'flight_number' => $segment->flightNumber,
                'departs_at' => $segment->departsAt->format('Y-m-d H:i:s'),
            ],
            [
                'carrier_name' => $segment->carrierName,
                'origin' => $segment->origin,
                'destination' => $segment->destination,
                'arrives_at' => $segment->arrivesAt->format('Y-m-d H:i:s'),
            ],
        ), $offer->segments);
    }

    public static function keyFor(FlightSegment $segment): string
    {
        return self::key($segment->carrierCode, $segment->flightNumber, $segment->departsAt->format('Y-m-d H:i:s'));
    }

    private static function key(string $carrier, string $number, string $departsAt): string
    {
        return "{$carrier}{$number}@{$departsAt}";
    }
}
