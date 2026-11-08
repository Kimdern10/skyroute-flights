<?php

use App\Mail\BookingConfirmed;
use App\Models\Booking;
use App\Models\Passenger;
use App\Models\User;
use App\Notifications\VerifyEmailCode;
use Illuminate\Auth\Notifications\ResetPassword;

test('the password reset email uses SkyRoute wording and the reset link', function () {
    $user = User::factory()->create(['email' => 'ada@example.com']);

    $mail = (new ResetPassword('reset-token'))->toMail($user);
    $html = (string) $mail->render();

    expect($mail->subject)->toBe('Reset your SkyRoute password')
        ->and($html)->toContain('Choose a new password')
        ->and($html)->toContain(e(route('password.reset', ['token' => 'reset-token', 'email' => 'ada@example.com'])));
});

test('emails show the SkyRoute logo and support details', function (string $email) {
    $html = match ($email) {
        'verification code' => (string) (new VerifyEmailCode('447829', 15))->toMail(User::factory()->create())->render(),
        'password reset' => (string) (new ResetPassword('token'))->toMail(User::factory()->create())->render(),
        'e-ticket' => (new BookingConfirmed(Booking::factory()->has(Passenger::factory())->create()))->render(),
    };

    expect($html)->toContain(asset('icon-192.png'))
        ->and($html)->toContain('SkyRoute logo')
        ->and($html)->toContain('support@skyroute.example')
        ->and($html)->not->toContain('laravel.com/img');
})->with(['verification code', 'password reset', 'e-ticket']);

test('the verification email shows the code in large type', function () {
    $html = (string) (new VerifyEmailCode('447829', 15))->toMail(User::factory()->create())->render();

    expect($html)->toContain('447829')->toContain('15 minutes');
});
