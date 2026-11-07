<?php

use App\Enums\TopUpStatus;
use App\Models\Setting;
use App\Models\TopUpRequest;
use App\Models\User;
use App\Notifications\TopUpReviewed;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    config(['services.paystack.secret' => 'sk_test_secret']);
    Setting::put(['paystack_currency' => 'NGN', 'paystack_rate' => '1500']);
    Notification::fake();
    Http::preventStrayRequests();
});

/**
 * Paystack answers "verify" with this payment.
 */
function fakePaystackVerify(string $status = 'success', int $amount = 7_500_000, string $currency = 'NGN'): void
{
    Http::fake([
        'api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => $status, 'amount' => $amount, 'currency' => $currency]]),
    ]);
}

function cardTopUp(User $user, array $attributes = []): TopUpRequest
{
    return TopUpRequest::factory()->for($user)->create(array_merge([
        'method' => 'card',
        'amount_cents' => 5000,
        'charged_amount' => 7_500_000,
        'charged_currency' => 'NGN',
        'payment_reference' => 'SRC-TEST123',
    ], $attributes));
}

function signedWebhook(array $payload): array
{
    $body = json_encode($payload);

    return [$body, hash_hmac('sha512', $body, 'sk_test_secret')];
}

test('paying by card sends the customer to Paystack for the amount in naira', function () {
    Http::fake([
        'api.paystack.co/transaction/initialize' => Http::response(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/abc123']]),
    ]);
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::account.wallet')
        ->set('cardAmount', '50')
        ->assertSee('NGN 75,000.00')
        ->call('payByCard')
        ->assertRedirect('https://checkout.paystack.com/abc123');

    $topUp = $user->topUpRequests()->sole();
    expect($topUp->method)->toBe('card')
        ->and($topUp->amount_cents)->toBe(5000)
        ->and($topUp->charged_amount)->toBe(7_500_000)
        ->and($topUp->status)->toBe(TopUpStatus::Pending);

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/transaction/initialize')
        && $request->hasHeader('Authorization', 'Bearer sk_test_secret')
        && $request['amount'] === 7_500_000
        && $request['currency'] === 'NGN'
        && $request['reference'] === $topUp->payment_reference
        && $request['callback_url'] === route('wallet.card.callback'));
});

test('the card box is hidden until a Paystack key is added', function () {
    config(['services.paystack.secret' => null]);

    $this->actingAs(User::factory()->create())->get(route('wallet'))->assertOk()->assertDontSee('Pay with card');
});

test('coming back from a successful payment adds the money and emails the customer', function () {
    fakePaystackVerify();
    $user = User::factory()->create();
    $topUp = cardTopUp($user);

    $this->actingAs($user)
        ->get(route('wallet.card.callback', ['reference' => 'SRC-TEST123']))
        ->assertRedirect(route('wallet'))
        ->assertSessionHas('wallet-success', '$50.00 was added to your wallet.');

    expect($user->fresh()->balance_cents)->toBe(5000)
        ->and($topUp->fresh()->status)->toBe(TopUpStatus::Approved);
    Notification::assertSentTo($user, TopUpReviewed::class);
});

test('a payment for less than the top-up adds nothing', function () {
    fakePaystackVerify(amount: 100);
    $user = User::factory()->create();
    $topUp = cardTopUp($user);

    $this->actingAs($user)->get(route('wallet.card.callback', ['reference' => 'SRC-TEST123']));

    expect($user->fresh()->balance_cents)->toBe(0)
        ->and($topUp->fresh()->status)->toBe(TopUpStatus::Rejected);
});

test('an unfinished payment stays waiting', function () {
    fakePaystackVerify('abandoned');
    $user = User::factory()->create();
    $topUp = cardTopUp($user);

    $this->actingAs($user)->get(route('wallet.card.callback', ['reference' => 'SRC-TEST123']))->assertSessionHas('wallet-error');

    expect($topUp->fresh()->status)->toBe(TopUpStatus::Pending)
        ->and($user->fresh()->balance_cents)->toBe(0);
});

test('the signed webhook adds the money once, even if the customer also comes back', function () {
    fakePaystackVerify();
    $user = User::factory()->create();
    cardTopUp($user);
    [$body, $signature] = signedWebhook(['event' => 'charge.success', 'data' => ['reference' => 'SRC-TEST123']]);

    $this->call('POST', route('paystack.webhook'), [], [], [], ['HTTP_X_PAYSTACK_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'], $body)->assertOk();
    $this->actingAs($user)->get(route('wallet.card.callback', ['reference' => 'SRC-TEST123']));

    expect($user->fresh()->balance_cents)->toBe(5000);
    $this->assertDatabaseCount('wallet_transactions', 1);
});

test('a webhook with a wrong signature is refused', function () {
    $user = User::factory()->create();
    cardTopUp($user);
    [$body] = signedWebhook(['event' => 'charge.success', 'data' => ['reference' => 'SRC-TEST123']]);

    $this->call('POST', route('paystack.webhook'), [], [], [], ['HTTP_X_PAYSTACK_SIGNATURE' => 'forged', 'CONTENT_TYPE' => 'application/json'], $body)->assertUnauthorized();

    expect($user->fresh()->balance_cents)->toBe(0);
    Http::assertNothingSent();
});

test('a customer cannot complete someone else\'s card payment', function () {
    fakePaystackVerify();
    $owner = User::factory()->create();
    cardTopUp($owner);
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->get(route('wallet.card.callback', ['reference' => 'SRC-TEST123']))
        ->assertSessionHas('wallet-error', 'We could not find that card payment.');

    expect($stranger->fresh()->balance_cents)->toBe(0);
});

test('the admin cannot approve a card payment by hand', function () {
    $topUp = cardTopUp(User::factory()->create());

    Livewire::actingAs(User::factory()->admin()->create())
        ->test('pages::admin.top-ups')
        ->call('approve', $topUp->id)
        ->assertForbidden();

    expect($topUp->fresh()->status)->toBe(TopUpStatus::Pending);
});
