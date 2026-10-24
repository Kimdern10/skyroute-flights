<?php

namespace App\Support;

/**
 * Amounts are kept as whole US cents so sums never pick up rounding errors.
 */
class Money
{
    /**
     * Format cents as dollars, e.g. 123450 → "$1,234.50".
     */
    public static function format(int $cents): string
    {
        return ($cents < 0 ? '-' : '').'$'.number_format(abs($cents) / 100, 2);
    }

    /**
     * Turn a typed dollar amount ("1,234.5") into cents.
     */
    public static function toCents(string|float|int $dollars): int
    {
        return (int) round((float) str_replace(',', '', (string) $dollars) * 100);
    }
}
