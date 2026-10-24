<?php

namespace App\Services\Flights;

use Carbon\CarbonImmutable;

/**
 * One take-off and landing. Times are the local time at each airport.
 */
final readonly class FlightSegment
{
    public function __construct(
        public string $carrierCode,
        public string $carrierName,
        public string $flightNumber,
        public string $origin,
        public string $originName,
        public string $destination,
        public string $destinationName,
        public CarbonImmutable $departsAt,
        public CarbonImmutable $arrivesAt,
        public ?string $aircraft = null,
    ) {}

    /**
     * @param  array{carrier_code: string, carrier_name: string, flight_number: string, origin: string, origin_name: string, destination: string, destination_name: string, departs_at: string, arrives_at: string, aircraft: string|null}  $segment
     */
    public static function fromArray(array $segment): self
    {
        return new self(
            carrierCode: $segment['carrier_code'],
            carrierName: $segment['carrier_name'],
            flightNumber: $segment['flight_number'],
            origin: $segment['origin'],
            originName: $segment['origin_name'],
            destination: $segment['destination'],
            destinationName: $segment['destination_name'],
            departsAt: CarbonImmutable::parse($segment['departs_at']),
            arrivesAt: CarbonImmutable::parse($segment['arrives_at']),
            aircraft: $segment['aircraft'],
        );
    }

    /**
     * @return array{carrier_code: string, carrier_name: string, flight_number: string, origin: string, origin_name: string, destination: string, destination_name: string, departs_at: string, arrives_at: string, aircraft: string|null}
     */
    public function toArray(): array
    {
        return [
            'carrier_code' => $this->carrierCode,
            'carrier_name' => $this->carrierName,
            'flight_number' => $this->flightNumber,
            'origin' => $this->origin,
            'origin_name' => $this->originName,
            'destination' => $this->destination,
            'destination_name' => $this->destinationName,
            'departs_at' => $this->departsAt->format('Y-m-d H:i:s'),
            'arrives_at' => $this->arrivesAt->format('Y-m-d H:i:s'),
            'aircraft' => $this->aircraft,
        ];
    }
}
