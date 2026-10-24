<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

/**
 * The PDF e-ticket for a booking.
 */
class ETicket
{
    public static function pdf(Booking $booking): string
    {
        return self::document($booking)->output();
    }

    public static function download(Booking $booking): Response
    {
        return self::document($booking)->download(self::filename($booking));
    }

    public static function filename(Booking $booking): string
    {
        return 'e-ticket-'.$booking->reference.'.pdf';
    }

    private static function document(Booking $booking): \Barryvdh\DomPDF\PDF
    {
        $booking->loadMissing('passengers');

        return Pdf::loadView('pdf.ticket', [
            'booking' => $booking,
            'site' => Setting::values(),
        ])->setPaper('a4');
    }
}
