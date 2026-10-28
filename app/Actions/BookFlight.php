<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\WalletTransactionType;
use App\Mail\BookingConfirmed;
use App\Models\Booking;
use App\Models\Flight;
use App\Models\User;
use App\Services\Flights\FlightOffer;
use App\Services\Flights\FlightProvider;
use App\Services\Flights\FlightProviderException;
use App\Services\Pricing;
use App\Services\SeatInventory;
use App\Services\Wallet;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Pay for a flight from the wallet and buy it from the airline.
 *
 * Seats and money are taken first inside one locked transaction (so the last seat can
 * only go to one person), then the airline is asked to issue the ticket. If the airline
 * says no, the seats and money are given back.
 */
class BookFlight
{
    public function __construct(
        private FlightProvider $provider,
        private SeatInventory $seats,
        private Pricing $pricing,
        private Wallet $wallet,
    ) {}

    /**
     * @param  list<array{title: string, given_name: string, family_name: string, gender: string, born_on: string}>  $passengers
     *
     * @throws BookingException
     */
    public function handle(User $user, FlightOffer $offer, array $passengers, string $email, string $phone): Booking
    {
        $flightIds = array_map(fn (Flight $flight): int => $flight->id, $this->seats->ensureFlights($offer));

        $booking = DB::transaction(function () use ($user, $offer, $passengers, $email, $phone, $flightIds): Booking {
            $flights = Flight::query()->whereKey($flightIds)->orderBy('id')->lockForUpdate()->get();
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $count = count($passengers);

            $fewestLeft = $flights->min(fn (Flight $flight): int => $flight->seatsLeft());

            if ($fewestLeft === 0) {
                throw BookingException::soldOut();
            }

            if ($fewestLeft < $count) {
                throw BookingException::notEnoughSeats($fewestLeft);
            }

            $price = $this->pricing->quote($offer, $count, $flights->firstWhere('id', $flightIds[0]));

            if ($price === null) {
                throw BookingException::cannotPrice();
            }

            if ($lockedUser->balance_cents < $price['total_cents']) {
                throw BookingException::lowBalance(Money::format($price['total_cents'] - $lockedUser->balance_cents));
            }

            $first = $offer->first();
            $last = $offer->last();

            $booking = $lockedUser->bookings()->create([
                'reference' => Booking::newReference(),
                'status' => BookingStatus::Pending,
                'offer_id' => $offer->id,
                'carrier_code' => $offer->airlineCode,
                'carrier_name' => $offer->airlineName,
                'origin' => $first->origin,
                'destination' => $last->destination,
                'departs_at' => $first->departsAt,
                'arrives_at' => $last->arrivesAt,
                'cabin' => $offer->cabin,
                'passengers_count' => $count,
                'segments' => array_map(fn ($segment): array => $segment->toArray(), $offer->segments),
                ...$price,
                'provider_amount' => $offer->amount,
                'provider_currency' => $offer->currency,
                'contact_email' => $email,
                'contact_phone' => $phone,
            ]);

            foreach ($passengers as $index => $passenger) {
                $booking->passengers()->create([...$passenger, 'provider_passenger_id' => $offer->passengerIds[$index] ?? null]);
            }

            foreach ($flights as $flight) {
                $flight->increment('seats_booked', $count);
                $booking->flights()->attach($flight->id, ['seats' => $count]);
            }

            $this->wallet->record($lockedUser, -$price['total_cents'], WalletTransactionType::Booking, "Ticket {$booking->reference}: {$first->origin} to {$last->destination}", booking: $booking);
            $user->balance_cents = $lockedUser->balance_cents;

            return $booking;
        });

        try {
            $order = $this->provider->book(
                $offer,
                array_values($booking->passengers->map(fn ($passenger): array => [
                    'provider_passenger_id' => (string) $passenger->provider_passenger_id,
                    'title' => $passenger->title,
                    'given_name' => $passenger->given_name,
                    'family_name' => $passenger->family_name,
                    'gender' => $passenger->gender,
                    'born_on' => $passenger->born_on->toDateString(),
                ])->all()),
                $email,
                $phone,
            );
        } catch (FlightProviderException $exception) {
            $this->release($booking, $exception->getMessage());
            $user->refresh();

            throw new BookingException($exception->getMessage().' You have not been charged.');
        }

        DB::transaction(function () use ($booking, $order): void {
            $booking->update([
                'status' => BookingStatus::Confirmed,
                'provider_order_id' => $order->id,
                'airline_reference' => $order->bookingReference,
            ]);

            foreach ($booking->passengers as $passenger) {
                $passenger->update(['ticket_number' => $order->ticketNumbers[$passenger->provider_passenger_id] ?? Booking::newTicketNumber()]);
            }
        });

        try {
            Mail::to($email)->send(new BookingConfirmed($booking->fresh(['passengers'])));
        } catch (\Throwable $exception) {
            report($exception);
        }

        return $booking;
    }

    /**
     * Give the seats and money back for a booking the airline refused.
     */
    public function release(Booking $booking, string $reason): void
    {
        DB::transaction(function () use ($booking, $reason): void {
            $booking = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($booking->status !== BookingStatus::Pending) {
                return;
            }

            $flights = $booking->flights()->orderBy('flights.id')->lockForUpdate()->get();

            // Every flight of the journey held one seat per passenger.
            foreach ($flights as $flight) {
                $flight->decrement('seats_booked', min($flight->seats_booked, $booking->passengers_count));
            }

            $user = User::query()->whereKey($booking->user_id)->lockForUpdate()->firstOrFail();
            $this->wallet->record($user, $booking->total_cents, WalletTransactionType::Refund, "Refund for {$booking->reference}: the airline could not issue the ticket", booking: $booking);

            $booking->update(['status' => BookingStatus::Failed, 'failure_reason' => $reason]);
        });
    }
}
