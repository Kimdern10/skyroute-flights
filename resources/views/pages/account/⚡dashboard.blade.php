<?php

use App\Enums\TopUpStatus;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('My account')] class extends Component {
    /**
     * @return Collection<int, Booking>
     */
    #[Computed]
    public function upcoming(): Collection
    {
        return Auth::user()->bookings()->confirmed()->where('departs_at', '>=', now()->subDay())->orderBy('departs_at')->limit(3)->get();
    }

    #[Computed]
    public function pendingTopUps(): int
    {
        return Auth::user()->topUpRequests()->where('status', TopUpStatus::Pending)->count();
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">Hello, {{ str(auth()->user()->name)->before(' ') }}</flux:heading>
        <flux:text>Your wallet, trips and tickets in one place.</flux:text>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <flux:card class="md:col-span-1">
            <flux:text>Wallet balance</flux:text>
            <div class="mt-1 text-3xl font-bold" data-test="balance">{{ auth()->user()->balance() }}</div>
            @if ($this->pendingTopUps)
                <flux:badge color="amber" size="sm" class="mt-2">{{ $this->pendingTopUps }} top-up waiting for approval</flux:badge>
            @endif
            <div class="mt-4">
                <flux:button size="sm" :href="route('wallet')" wire:navigate>Top up</flux:button>
            </div>
        </flux:card>

        <flux:card class="md:col-span-2">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Upcoming trips</flux:heading>
                <flux:button size="sm" variant="primary" :href="route('flights.index')" icon="magnifying-glass">Search flights</flux:button>
            </div>
            <div class="mt-4 grid gap-3">
                @forelse ($this->upcoming as $booking)
                    <a href="{{ route('bookings.show', $booking) }}" class="flex items-center justify-between gap-4 rounded-lg border border-zinc-200 p-3 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                        <div>
                            <div class="font-semibold">{{ $booking->origin }} → {{ $booking->destination }}</div>
                            <flux:text size="sm">{{ $booking->departs_at->format('D j M Y, H:i') }} · {{ $booking->carrier_name }}</flux:text>
                        </div>
                        <flux:badge>{{ $booking->reference }}</flux:badge>
                    </a>
                @empty
                    <flux:text>No trips yet. Find a flight and book it from your wallet.</flux:text>
                @endforelse
            </div>
        </flux:card>
    </div>
</div>
