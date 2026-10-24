<?php

use App\Models\Booking;
use App\Models\Passenger;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

test('visitors must log in before booking', function () {
    $this->get(route('flights.book', 'off_123'))->assertRedirect(route('login'));
});

test('the booking form books the flight and opens the confirmation', function () {
    Mail::fake();
    fakeDuffel();
    $user = User::factory()->withBalance(1000)->create(['name' => 'Ada Okafor']);

    $component = Livewire::actingAs($user)
        ->test('pages::flights.book', ['offer' => 'off_0000AgHGicvyjqDEO1qxfh'])
        ->assertSee('$435.00')
        ->assertSet('passengers.0.given_name', 'Ada')
        ->set('passengers.0.born_on', '1990-04-12')
        ->set('phone', '+14155550123')
        ->call('book');

    $booking = Booking::query()->sole();
    $component->assertRedirect(route('bookings.show', $booking));
    expect($user->fresh()->phone)->toBe('+14155550123');
});

test('the booking form checks passenger details', function () {
    fakeDuffel();

    Livewire::actingAs(User::factory()->withBalance(1000)->create())
        ->test('pages::flights.book', ['offer' => 'off_0000AgHGicvyjqDEO1qxfh'])
        ->set('passengers.0.given_name', '')
        ->set('passengers.0.born_on', now()->addDay()->toDateString())
        ->set('phone', '0803 000 0000')
        ->call('book')
        ->assertHasErrors(['passengers.0.given_name', 'passengers.0.born_on', 'phone']);

    expect(Booking::query()->count())->toBe(0);
});

test('a customer without enough money is sent to top up', function () {
    fakeDuffel();

    Livewire::actingAs(User::factory()->withBalance(50)->create())
        ->test('pages::flights.book', ['offer' => 'off_0000AgHGicvyjqDEO1qxfh'])
        ->assertSee('You need $385.00 more in your wallet')
        ->assertSee('Top up my wallet');
});

test('the confirmation shows the reference and ticket numbers to its owner', function () {
    $booking = Booking::factory()->has(Passenger::factory(['ticket_number' => '1252106312810']))->create();

    $this->actingAs($booking->user)
        ->get(route('bookings.show', $booking))
        ->assertOk()
        ->assertSee($booking->reference)
        ->assertSee('1252106312810')
        ->assertSee('Download e-ticket');
});

test('other customers cannot see someone else\'s booking or ticket', function () {
    $booking = Booking::factory()->has(Passenger::factory())->create();
    $stranger = User::factory()->create();

    $this->actingAs($stranger)->get(route('bookings.show', $booking))->assertForbidden();
    $this->actingAs($stranger)->get(route('bookings.ticket', $booking))->assertForbidden();
});

test('the owner can download the PDF e-ticket', function () {
    $booking = Booking::factory()->has(Passenger::factory())->create();

    $response = $this->actingAs($booking->user)->get(route('bookings.ticket', $booking));

    $response->assertOk()->assertHeader('content-type', 'application/pdf');
    expect($response->headers->get('content-disposition'))->toContain("e-ticket-{$booking->reference}.pdf");
});

test('my booking finds a booking by reference and last name', function () {
    $booking = Booking::factory()->has(Passenger::factory(['family_name' => 'Okafor']))->create();

    Livewire::test('pages::my-booking')
        ->set('reference', strtolower($booking->reference))
        ->set('lastName', 'OKAFOR')
        ->call('find')
        ->assertRedirectContains('/my-booking/'.$booking->reference);
});

test('my booking does not say which part was wrong', function () {
    $booking = Booking::factory()->has(Passenger::factory(['family_name' => 'Okafor']))->create();

    Livewire::test('pages::my-booking')
        ->set('reference', $booking->reference)
        ->set('lastName', 'Smith')
        ->call('find')
        ->assertNoRedirect()
        ->assertSee('We could not find a booking with that reference and last name');
});

test('the signed my-booking link shows the booking and its PDF without a login', function () {
    $booking = Booking::factory()->has(Passenger::factory())->create();

    $this->get(URL::temporarySignedRoute('my-booking.show', now()->addMinutes(30), $booking))->assertOk()->assertSee($booking->reference);
    $this->get(URL::temporarySignedRoute('my-booking.ticket', now()->addMinutes(30), $booking))->assertOk()->assertHeader('content-type', 'application/pdf');
});

test('the my-booking pages need a valid signature', function () {
    $booking = Booking::factory()->create();

    $this->get(route('my-booking.show', $booking))->assertForbidden();
    $this->get(route('my-booking.ticket', $booking))->assertForbidden();
});
