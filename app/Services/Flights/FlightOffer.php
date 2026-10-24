<?php

namespace App\Services\Flights;

use Illuminate\Support\Carbon;

/**
 * A priced, bookable flight returned by the airline API.
 */
final readonly class FlightOffer
{
    /**
     * @param  list<FlightSegment>  $segments
     * @param  list<string>  $passengerIds  The airline's id for each seat in the offer.
     */
    public function __construct(
        public string $id,
        public string $amount,
        public string $currency,
        public string $airlineName,
        public string $airlineCode,
        public ?string $airlineLogo,
        public array $segments,
        public array $passengerIds,
        public string $cabin,
        public int $durationMinutes,
        public ?Carbon $expiresAt = null,
    ) {}

    public function first(): FlightSegment
    {
        return $this->segments[0];
    }

    public function last(): FlightSegment
    {
        return $this->segments[array_key_last($this->segments)];
    }

    public function stops(): int
    {
        return count($this->segments) - 1;
    }

    public function duration(): string
    {
        return intdiv($this->durationMinutes, 60).'h '.str_pad((string) ($this->durationMinutes % 60), 2, '0', STR_PAD_LEFT).'m';
    }

    /**
     * Same flights, same times: used to drop repeat fares of one journey.
     */
    public function journeyKey(): string
    {
        return collect($this->segments)
            ->map(fn (FlightSegment $segment): string => $segment->carrierCode.$segment->flightNumber.'@'.$segment->departsAt->format('YmdHi'))
            ->implode('|');
    }
}
