<?php

use App\Models\Booking;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Livewire\Component;

new class extends Component {
    public string $reference = '';

    public string $lastName = '';

    public ?string $error = null;

    public function find(): void
    {
        $this->error = null;

        $this->validate([
            'reference' => ['required', 'string', 'size:6'],
            'lastName' => ['required', 'string', 'max:60'],
        ], [], ['lastName' => 'last name']);

        $key = 'my-booking:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->error = 'Too many tries. Please wait a minute and try again.';

            return;
        }

        RateLimiter::hit($key, 60);

        $booking = Booking::query()
            ->where('reference', strtoupper(trim($this->reference)))
            ->whereHas('passengers', fn ($query) => $query->whereRaw('LOWER(family_name) = ?', [mb_strtolower(trim($this->lastName))]))
            ->first();

        if (! $booking) {
            $this->error = 'We could not find a booking with that reference and last name. Please check them and try again.';

            return;
        }

        $this->redirect(URL::temporarySignedRoute('my-booking.show', now()->addMinutes(30), $booking));
    }

    public function render()
    {
        return $this->view()->layout('layouts::site', ['current' => 'my-booking'])->title('My booking');
    }
}; ?>

<div>
    <section class="page-hero">
        <div class="container">
            <h1>Find my booking</h1>
            <p>Enter your booking reference and a passenger's last name to see the ticket and download it again.</p>
        </div>
    </section>
    <div class="container" style="padding-bottom: 60px">
        <form class="panel lookup" wire:submit="find">
            @if ($error)
                <div class="alert" role="alert">{{ $error }}</div>
            @endif
            <div class="field">
                <label for="reference">Booking reference</label>
                <input id="reference" wire:model="reference" maxlength="6" placeholder="e.g. K7PQ2M" autocomplete="off" style="text-transform: uppercase; letter-spacing: .12em; font-weight: 700">
                @error('reference') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div class="field" style="margin-top: 14px">
                <label for="last-name">Last name</label>
                <input id="last-name" wire:model="lastName" autocomplete="family-name">
                @error('lastName') <span class="error">{{ $message }}</span> @enderror
            </div>
            <button class="btn btn--primary btn--block" type="submit" style="margin-top: 18px">Find booking</button>
            <p class="muted small" style="margin: 14px 0 0">Your reference is the six-letter code in your confirmation email.</p>
        </form>
    </div>
</div>
