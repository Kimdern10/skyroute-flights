<?php

namespace App\Actions;

use RuntimeException;

/**
 * A booking could not be made. The message is safe to show to the customer.
 */
class BookingException extends RuntimeException
{
    public static function soldOut(): self
    {
        return new self('Sorry, this flight is fully booked. Please choose another flight.');
    }

    public static function notEnoughSeats(int $left): self
    {
        return new self("Only {$left} ".str('seat')->plural($left).' left on this flight. Please book fewer passengers or choose another flight.');
    }

    public static function lowBalance(string $needed): self
    {
        return new self("Your wallet balance is too low. Please top up {$needed} or more, then try again.");
    }

    public static function cannotPrice(): self
    {
        return new self('This fare cannot be priced right now. Please choose another flight.');
    }
}
