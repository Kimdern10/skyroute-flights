<?php

use App\Models\User;
use App\Notifications\VerifyEmailCode;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

/**
 * Send a code to the user and return the code from the email.
 */
function emailedCode(User $user): string
{
    Notification::fake();
    $user->sendEmailVerificationNotification();

    $code = null;
    Notification::assertSentTo($user, VerifyEmailCode::class, function (VerifyEmailCode $notification) use (&$code) {
        $code = $notification->code;

        return true;
    });

    return $code;
}

test('signing up emails a 6-digit code instead of a link', function () {
    Notification::fake();

    $this->post(route('register.store'), [
        'name' => 'Ada Okafor',
        'email' => 'ada@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::query()->where('email', 'ada@example.com')->sole();

    Notification::assertSentTo($user, VerifyEmailCode::class, fn (VerifyEmailCode $notification) => preg_match('/^\d{6}$/', $notification->code) === 1);
    expect($user->email_verification_code)->not->toBeNull()
        ->and($user->email_verification_code_expires_at->isFuture())->toBeTrue();
});

test('the code is stored hashed, not as typed', function () {
    $user = User::factory()->unverified()->create();
    $code = emailedCode($user);

    expect($user->fresh()->email_verification_code)->not->toBe($code);
});

test('the right code confirms the email and opens the account', function () {
    $user = User::factory()->unverified()->create();
    $code = emailedCode($user);
    Event::fake([Verified::class]);

    Livewire::actingAs($user)
        ->test('verify-email-code')
        ->set('code', $code)
        ->call('verify')
        ->assertRedirect(route('dashboard'));

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue()
        ->and($user->fresh()->email_verification_code)->toBeNull();
    Event::assertDispatched(Verified::class);
});

test('a confirmed admin goes to the admin dashboard', function () {
    $admin = User::factory()->unverified()->admin()->create();
    $code = emailedCode($admin);

    Livewire::actingAs($admin)
        ->test('verify-email-code')
        ->set('code', $code)
        ->call('verify')
        ->assertRedirect(route('admin.dashboard'));
});

test('a wrong code does not confirm the email', function () {
    $user = User::factory()->unverified()->create();
    $code = emailedCode($user);

    Livewire::actingAs($user)
        ->test('verify-email-code')
        ->set('code', $code === '000000' ? '111111' : '000000')
        ->call('verify')
        ->assertHasErrors('code')
        ->assertNoRedirect();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('an expired code does not confirm the email', function () {
    $user = User::factory()->unverified()->create();
    $code = emailedCode($user);

    $this->travel(User::VERIFICATION_CODE_MINUTES + 1)->minutes();

    Livewire::actingAs($user)
        ->test('verify-email-code')
        ->set('code', $code)
        ->call('verify')
        ->assertHasErrors('code');

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('guessing is stopped after five wrong tries', function () {
    $user = User::factory()->unverified()->create();
    $code = emailedCode($user);
    $wrong = $code === '000000' ? '111111' : '000000';

    $component = Livewire::actingAs($user)->test('verify-email-code');

    foreach (range(1, 5) as $try) {
        $component->set('code', $wrong)->call('verify');
    }

    $component->set('code', $code)->call('verify')->assertHasErrors('code')->assertSee('Too many tries');

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('a new code replaces the old one and can only be asked for once a minute', function () {
    $user = User::factory()->unverified()->create();
    $oldCode = emailedCode($user);

    Livewire::actingAs($user)
        ->test('verify-email-code')
        ->call('resend')
        ->assertSee('A new code is on its way')
        ->call('resend')
        ->assertHasErrors('code');

    // The first email plus one resend; the second resend was refused.
    Notification::assertSentToTimes($user, VerifyEmailCode::class, 2);
    expect($user->fresh()->verifyEmailWithCode($oldCode))->toBeFalse();
});

test('the verify page asks for the code', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertOk()
        ->assertSee('We sent a 6-digit code to')
        ->assertSee('Send a new code');
});
