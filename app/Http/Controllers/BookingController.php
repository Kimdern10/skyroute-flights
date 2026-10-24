<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Support\ETicket;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class BookingController extends Controller
{
    /**
     * The booking confirmation, for the customer who booked it (or the admin).
     */
    public function show(Booking $booking): View
    {
        Gate::authorize('view', $booking);

        return $this->page($booking, route('bookings.ticket', $booking));
    }

    public function ticket(Booking $booking): Response
    {
        Gate::authorize('view', $booking);
        abort_unless($booking->isConfirmed(), Response::HTTP_NOT_FOUND);

        return ETicket::download($booking);
    }

    /**
     * Reached from "My booking" with a short-lived signed link, so no login is needed.
     */
    public function lookup(Request $request, Booking $booking): View
    {
        return $this->page($booking, URL::temporarySignedRoute('my-booking.ticket', now()->addMinutes(30), $booking));
    }

    public function lookupTicket(Booking $booking): Response
    {
        abort_unless($booking->isConfirmed(), Response::HTTP_NOT_FOUND);

        return ETicket::download($booking);
    }

    private function page(Booking $booking, string $ticketUrl): View
    {
        return view('bookings.show', [
            'booking' => $booking->load('passengers'),
            'ticketUrl' => $ticketUrl,
            'justBooked' => session('booked') === $booking->reference,
        ]);
    }
}
