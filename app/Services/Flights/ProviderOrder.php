<?php

namespace App\Services\Flights;

/**
 * The airline's confirmation of a purchase.
 */
final readonly class ProviderOrder
{
    /**
     * @param  array<string, string>  $ticketNumbers  E-ticket number keyed by the airline's passenger id.
     */
    public function __construct(
        public string $id,
        public ?string $bookingReference,
        public array $ticketNumbers = [],
    ) {}
}
