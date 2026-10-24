<?php

namespace App\Services\Flights;

/**
 * What the visitor typed in the search box.
 */
final readonly class FlightSearch
{
    public function __construct(
        public string $origin,
        public string $destination,
        public string $date,
        public int $passengers = 1,
        public string $cabin = 'economy',
    ) {}

    public function cacheKey(): string
    {
        return 'flight-search:'.implode(':', [$this->origin, $this->destination, $this->date, $this->passengers, $this->cabin]);
    }
}
