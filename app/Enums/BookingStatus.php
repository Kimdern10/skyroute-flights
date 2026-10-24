<?php

namespace App\Enums;

enum BookingStatus: string
{
    /** Seats and money are held while the airline confirms the order. */
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    /** The airline refused the order; the seats and money were given back. */
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Confirmed => 'green',
            self::Failed, self::Cancelled => 'red',
        };
    }
}
