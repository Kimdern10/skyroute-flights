<?php

use App\Enums\TopUpStatus;
use App\Models\TopUpRequest;
use App\Models\User;
use Livewire\Livewire;

test('a customer can ask for a top-up', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::account.wallet')
        ->set('amount', '250.50')
        ->set('paymentReference', 'TRF123456')
        ->call('requestTopUp')
        ->assertHasNoErrors();

    $request = $user->topUpRequests()->sole();
    expect($request->amount_cents)->toBe(25050)
        ->and($request->status)->toBe(TopUpStatus::Pending)
        ->and($user->fresh()->balance_cents)->toBe(0);
});

test('a top-up needs an amount and a payment reference', function () {
    Livewire::actingAs(User::factory()->create())
        ->test('pages::account.wallet')
        ->set('amount', '5')
        ->call('requestTopUp')
        ->assertHasErrors(['amount', 'paymentReference']);
});

test('only three top-ups can wait at once', function () {
    $user = User::factory()->create();
    TopUpRequest::factory()->count(3)->for($user)->create();

    Livewire::actingAs($user)
        ->test('pages::account.wallet')
        ->set('amount', '100')
        ->set('paymentReference', 'TRF1')
        ->call('requestTopUp')
        ->assertHasErrors('amount');

    expect($user->topUpRequests()->count())->toBe(3);
});

test('the wallet page shows the balance and statement', function () {
    $user = User::factory()->withBalance(120)->create();

    $this->actingAs($user)->get(route('wallet'))->assertOk()->assertSee('$120.00');
});
