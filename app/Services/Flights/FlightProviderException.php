<?php

namespace App\Services\Flights;

use RuntimeException;

/**
 * The airline API is not set up, cannot be reached, or refused the request.
 * The message is safe to show to visitors.
 */
class FlightProviderException extends RuntimeException
{
    public static function notConfigured(): self
    {
        return new self('Flight search is not connected yet. Please try again later.');
    }
}
