<?php

namespace App\Enums;

enum WalletTransactionType: string
{
    case TopUp = 'top_up';
    case Booking = 'booking';
    case Refund = 'refund';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::TopUp => 'Top-up',
            self::Booking => 'Ticket',
            self::Refund => 'Refund',
            self::Adjustment => 'Adjustment',
        };
    }
}
