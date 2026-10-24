<?php

use App\Enums\TopUpStatus;
use App\Enums\WalletTransactionType;
use App\Models\Booking;
use App\Models\Flight;
use App\Models\Passenger;
use App\Models\Setting;
use App\Models\TopUpRequest;
use App\Models\User;
use Livewire\Livewire;

test('customers cannot open the admin', function (string $route) {
    $this->actingAs(User::factory()->create())->get(route($route))->assertForbidden();
})->with(['admin.dashboard', 'admin.bookings.index', 'admin.flights', 'admin.top-ups', 'admin.customers', 'admin.settings']);

test('the admin pages open for the admin', function (string $route) {
    Booking::factory()->has(Passenger::factory())->create();
    Flight::factory()->create();
    TopUpRequest::factory()->create();

    $this->actingAs(User::factory()->admin()->create())->get(route($route))->assertOk();
})->with(['admin.dashboard', 'admin.bookings.index', 'admin.flights', 'admin.top-ups', 'admin.customers', 'admin.settings']);

test('the admin can open any booking', function () {
    $booking = Booking::factory()->has(Passenger::factory(['family_name' => 'Mensah']))->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('admin.bookings.show', $booking))->assertOk()->assertSee('Mensah');
    $this->actingAs($admin)->get(route('bookings.ticket', $booking))->assertOk();
});

test('approving a top-up adds the money once', function () {
    $request = TopUpRequest::factory()->create(['amount_cents' => 50000]);
    $admin = User::factory()->admin()->create();

    $component = Livewire::actingAs($admin)->test('pages::admin.top-ups')->call('approve', $request->id);
    $component->call('approve', $request->id);

    expect($request->user->fresh()->balance_cents)->toBe(50000)
        ->and($request->fresh()->status)->toBe(TopUpStatus::Approved)
        ->and($request->fresh()->reviewed_by)->toBe($admin->id);
    $this->assertDatabaseCount('wallet_transactions', 1);
});

test('rejecting a top-up adds nothing and keeps the note for the customer', function () {
    $request = TopUpRequest::factory()->create();

    Livewire::actingAs(User::factory()->admin()->create())
        ->test('pages::admin.top-ups')
        ->set("notes.{$request->id}", 'No transfer found.')
        ->call('reject', $request->id);

    expect($request->fresh()->status)->toBe(TopUpStatus::Rejected)
        ->and($request->fresh()->admin_note)->toBe('No transfer found.')
        ->and($request->user->fresh()->balance_cents)->toBe(0);
});

test('the admin can adjust a balance but not below zero', function () {
    $customer = User::factory()->withBalance(30)->create();

    Livewire::actingAs(User::factory()->admin()->create())
        ->test('pages::admin.customers')
        ->call('startAdjust', $customer->id)
        ->set('amount', '-50')
        ->set('reason', 'Correction')
        ->call('adjust')
        ->assertHasErrors('amount')
        ->set('amount', '-10')
        ->call('adjust')
        ->assertHasNoErrors();

    expect($customer->fresh()->balance_cents)->toBe(2000);
    $this->assertDatabaseHas('wallet_transactions', ['user_id' => $customer->id, 'type' => WalletTransactionType::Adjustment->value, 'amount_cents' => -1000]);
});

test('the admin can change seats and set a fixed price on a flight', function () {
    $flight = Flight::factory()->create(['seats_booked' => 4]);

    Livewire::actingAs(User::factory()->admin()->create())
        ->test('pages::admin.flights')
        ->call('edit', $flight->id)
        ->set('seatsTotal', '3')
        ->call('save')
        ->assertHasErrors('seatsTotal')
        ->set('seatsTotal', '30')
        ->set('priceOverride', '299')
        ->call('save')
        ->assertHasNoErrors();

    expect($flight->fresh())
        ->seats_total->toBe(30)
        ->price_override_cents->toBe(29900);
});

test('closing sales marks a flight fully booked', function () {
    $flight = Flight::factory()->create(['seats_booked' => 7]);

    Livewire::actingAs(User::factory()->admin()->create())->test('pages::admin.flights')->call('closeSales', $flight->id);

    expect($flight->fresh()->isFull())->toBeTrue();
});

test('the admin saves pricing and the API key, stored encrypted', function () {
    Livewire::actingAs(User::factory()->admin()->create())
        ->test('pages::admin.settings')
        ->set('markup_percent', '8')
        ->set('markup_fixed', '20')
        ->set('duffel_token', 'duffel_test_abc123')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('hasToken', true);

    expect(Setting::get('markup_percent'))->toBe('8')
        ->and(Setting::int('markup_fixed_cents'))->toBe(2000)
        ->and(Setting::get('duffel_token'))->toBe('duffel_test_abc123');
    $this->assertDatabaseMissing('settings', ['value' => 'duffel_test_abc123']);
});

test('an API key that is not a Duffel key is refused', function () {
    Livewire::actingAs(User::factory()->admin()->create())
        ->test('pages::admin.settings')
        ->set('duffel_token', 'sk_live_123')
        ->call('save')
        ->assertHasErrors('duffel_token');
});

test('the create admin command makes an admin login', function () {
    $this->artisan('app:create-admin', ['--name' => 'Owner', '--email' => 'owner@example.com', '--password' => 'secret-pass-123'])->assertSuccessful();

    expect(User::query()->where('email', 'owner@example.com')->sole()->is_admin)->toBeTrue();
});
