<?php

use App\Actions\BookFlight;
use App\Actions\BookingException;
use App\Enums\BookingStatus;
use App\Enums\WalletTransactionType;
use App\Mail\BookingConfirmed;
use App\Models\Booking;
use App\Models\Flight;
use App\Models\User;
use App\Services\Flights\FlightProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

function bookOffer(User $user, int $passengers = 1): Booking
{
    $offer = app(FlightProvider::class)->find('off_0000AgHGicvyjqDEO1qxfh');

    return app(BookFlight::class)->handle(
        $user,
        $offer,
        array_map(fn (int $index): array => passengerDetails('Okafor'.$index), range(1, $passengers)),
        'ada@example.com',
        '+14155550123',
    );
}

test('booking takes the money, counts the seat and issues the ticket', function () {
    fakeDuffel();
    $user = User::factory()->withBalance(1000)->create();

    $booking = bookOffer($user);

    expect($booking->status)->toBe(BookingStatus::Confirmed)
        ->and($booking->reference)->toMatch('/^[A-HJ-NP-Z2-9]{6}$/')
        ->and($booking->airline_reference)->toBe('RZPNX8')
        ->and($booking->total_cents)->toBe(43500)
        ->and($booking->passengers->first()->ticket_number)->toBe('1252106312811')
        ->and($user->fresh()->balance_cents)->toBe(100000 - 43500);

    expect(Flight::query()->sole()->seats_booked)->toBe(1);

    $this->assertDatabaseHas('wallet_transactions', [
        'user_id' => $user->id,
        'type' => WalletTransactionType::Booking->value,
        'amount_cents' => -43500,
        'balance_after_cents' => 56500,
    ]);

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/air/orders')
        && $request['data']['selected_offers'] === ['off_0000AgHGicvyjqDEO1qxfh']
        && $request['data']['payments'][0] === ['type' => 'balance', 'currency' => 'USD', 'amount' => '400.00']
        && $request['data']['passengers'][0]['id'] === 'pas_1'
        && $request['data']['passengers'][0]['family_name'] === 'Okafor1');
});

test('the e-ticket is emailed with the PDF attached', function () {
    fakeDuffel();
    $booking = bookOffer(User::factory()->withBalance(1000)->create());

    Mail::assertSent(BookingConfirmed::class, function (BookingConfirmed $mail) use ($booking) {
        $attachment = $mail->attachments()[0];

        return $mail->hasTo('ada@example.com')
            && $mail->booking->is($booking)
            && $attachment->as === "e-ticket-{$booking->reference}.pdf"
            && $attachment->mime === 'application/pdf';
    });
});

test('a fully booked flight cannot be booked and nothing is charged', function () {
    fakeDuffel();
    Flight::factory()->create(['carrier_code' => 'ZZ', 'flight_number' => '117', 'departs_at' => '2026-11-20 08:25:00', 'seats_total' => 5, 'seats_booked' => 5]);
    $user = User::factory()->withBalance(1000)->create();

    expect(fn () => bookOffer($user))->toThrow(BookingException::class, 'fully booked');

    expect($user->fresh()->balance_cents)->toBe(100000)
        ->and(Booking::query()->count())->toBe(0);
    Http::assertNotSent(fn (Request $request) => str_ends_with($request->url(), '/air/orders'));
});

test('the last seat goes to the first customer only', function () {
    fakeDuffel();
    Flight::factory()->create(['carrier_code' => 'ZZ', 'flight_number' => '117', 'departs_at' => '2026-11-20 08:25:00', 'seats_total' => 1]);

    bookOffer(User::factory()->withBalance(1000)->create());

    expect(fn () => bookOffer(User::factory()->withBalance(1000)->create()))->toThrow(BookingException::class, 'fully booked');
    expect(Flight::query()->sole()->seats_booked)->toBe(1);
});

test('a group larger than the seats left is refused', function () {
    fakeDuffel([duffelOffer(passengers: 3)]);
    Flight::factory()->create(['carrier_code' => 'ZZ', 'flight_number' => '117', 'departs_at' => '2026-11-20 08:25:00', 'seats_total' => 2]);

    expect(fn () => bookOffer(User::factory()->withBalance(5000)->create(), 3))->toThrow(BookingException::class, 'Only 2 seats left');
});

test('a low wallet balance stops the booking', function () {
    fakeDuffel();
    $user = User::factory()->withBalance(100)->create();

    expect(fn () => bookOffer($user))->toThrow(BookingException::class, 'top up $335.00');

    expect($user->fresh()->balance_cents)->toBe(10000)
        ->and(Flight::query()->sole()->seats_booked)->toBe(0);
});

test('when the airline refuses, the money and seat are given back', function () {
    fakeDuffel(order: ['errors' => [['code' => 'offer_no_longer_available', 'message' => 'Gone']]], orderStatus: 422);
    $user = User::factory()->withBalance(1000)->create();

    expect(fn () => bookOffer($user))->toThrow(BookingException::class, 'You have not been charged');

    $booking = Booking::query()->sole();
    expect($booking->status)->toBe(BookingStatus::Failed)
        ->and($user->fresh()->balance_cents)->toBe(100000)
        ->and(Flight::query()->sole()->seats_booked)->toBe(0);
    $this->assertDatabaseHas('wallet_transactions', ['booking_id' => $booking->id, 'type' => WalletTransactionType::Refund->value, 'amount_cents' => 43500]);
    Mail::assertNothingSent();
});

test('our own ticket number is used when the airline sends none', function () {
    fakeDuffel(order: ['data' => ['id' => 'ord_1', 'booking_reference' => 'ABC123', 'documents' => []]]);

    $booking = bookOffer(User::factory()->withBalance(1000)->create());

    expect($booking->passengers->first()->ticket_number)->toMatch('/^900\d{10}$/');
});
