<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /**
     * Customers see their own bookings; the admin sees all of them.
     */
    public function view(User $user, Booking $booking): bool
    {
        return $user->is_admin || $booking->user_id === $user->id;
    }
}
