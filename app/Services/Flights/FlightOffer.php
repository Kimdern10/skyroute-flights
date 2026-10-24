<?php

namespace App\Services\Flights;

use Carbon\CarbonImmutable;

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
        public ?CarbonImmutable $expiresAt = null,
    ) {}

    /**
     * Plain values, so the offer can be cached (the cache only stores arrays, not objects).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'airline_name' => $this->airlineName,
            'airline_code' => $this->airlineCode,
            'airline_logo' => $this->airlineLogo,
            'segments' => array_map(fn (FlightSegment $segment): array => $segment->toArray(), $this->segments),
            'passenger_ids' => $this->passengerIds,
            'cabin' => $this->cabin,
            'duration_minutes' => $this->durationMinutes,
            'expires_at' => $this->expiresAt?->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $offer
     */
    public static function fromArray(array $offer): self
    {
        return new self(
            id: $offer['id'],
            amount: $offer['amount'],
            currency: $offer['currency'],
            airlineName: $offer['airline_name'],
            airlineCode: $offer['airline_code'],
            airlineLogo: $offer['airline_logo'],
            segments: array_map(FlightSegment::fromArray(...), $offer['segments']),
            passengerIds: $offer['passenger_ids'],
            cabin: $offer['cabin'],
            durationMinutes: $offer['duration_minutes'],
            expiresAt: $offer['expires_at'] ? CarbonImmutable::parse($offer['expires_at']) : null,
        );
    }

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
