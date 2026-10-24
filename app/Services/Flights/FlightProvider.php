<?php

namespace App\Services\Flights;

use Illuminate\Support\Collection;

/**
 * Anything that can find and book real flights (Duffel today; another airline API later).
 */
interface FlightProvider
{
    /**
     * Whether an API key has been added, so searches can run.
     */
    public function isConfigured(): bool;

    /**
     * Find bookable flights for one day, cheapest first.
     *
     * @return Collection<int, FlightOffer>
     *
     * @throws FlightProviderException
     */
    public function search(FlightSearch $search): Collection;

    /**
     * Get the latest price and details of one offer, or null when it has expired.
     *
     * @throws FlightProviderException
     */
    public function find(string $offerId): ?FlightOffer;

    /**
     * Buy the offer with the airline.
     *
     * @param  list<array{provider_passenger_id: string, title: string, given_name: string, family_name: string, gender: string, born_on: string}>  $passengers
     *
     * @throws FlightProviderException
     */
    public function book(FlightOffer $offer, array $passengers, string $email, string $phone): ProviderOrder;
}
