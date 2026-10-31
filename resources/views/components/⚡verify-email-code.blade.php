<?php

use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

new class extends Component {
    public string $code = '';

    public ?string $message = null;

    public function verify(): void
    {
        $this->message = null;
        $this->validate(['code' => ['required', 'digits:6']], ['code.digits' => 'Enter the 6-digit code from the email.']);

        $user = Auth::user();
        $key = 'verify-email-code:'.$user->id;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('code', 'Too many tries. Wait '.RateLimiter::availableIn($key).' seconds and try again.');

            return;
        }

        if (! $user->verifyEmailWithCode($this->code)) {
            RateLimiter::hit($key, 60);
            $this->reset('code');
            $this->addError('code', 'That code is wrong or has expired. Check the latest email, or send a new code.');

            return;
        }

        RateLimiter::clear($key);
        event(new Verified($user));

        $this->redirectIntended(route($user->is_admin ? 'admin.dashboard' : 'dashboard', absolute: false));
    }

    public function resend(): void
    {
        $user = Auth::user();
        $key = 'resend-email-code:'.$user->id;

        if (RateLimiter::tooManyAttempts($key, 1)) {
            $this->message = null;
            $this->addError('code', 'Please wait '.RateLimiter::availableIn($key).' seconds before asking for another code.');

            return;
        }

        RateLimiter::hit($key, 60);
        $user->sendEmailVerificationNotification();

        $this->reset('code');
        $this->resetErrorBag();
        $this->message = 'A new code is on its way to '.$user->email.'.';
    }
}; ?>

<div class="flex flex-col gap-6">
    <form wire:submit="verify" class="flex flex-col items-center gap-4">
        <div x-data x-init="$nextTick(() => $el.querySelector('input')?.focus())">
            <flux:otp name="code" wire:model="code" length="6" label="Verification code" label:sr-only class="mx-auto" />
        </div>

        @if ($this->message)
            <flux:text class="text-center font-medium !text-green-600 dark:!text-green-400" role="status">{{ $this->message }}</flux:text>
        @endif

        <flux:button type="submit" variant="primary" class="w-full" data-test="verify-code-button">
            Confirm email
        </flux:button>
    </form>

    <div class="flex flex-col items-center gap-2 text-sm">
        <flux:text>Didn't get it? Check your spam folder.</flux:text>
        <flux:button variant="ghost" size="sm" wire:click="resend" data-test="resend-code-button">Send a new code</flux:button>
    </div>
</div>
