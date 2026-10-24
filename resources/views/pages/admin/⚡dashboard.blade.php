<?php

use App\Enums\BookingStatus;
use App\Enums\TopUpStatus;
use App\Models\Booking;
use App\Models\TopUpRequest;
use App\Models\User;
use App\Support\Money;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Admin')] class extends Component {
    /**
     * @return array<string, string|int>
     */
    #[Computed]
    public function stats(): array
    {
        $confirmed = Booking::query()->confirmed();

        return [
            'Tickets sold' => (clone $confirmed)->sum('passengers_count'),
            'Sales' => Money::format((int) (clone $confirmed)->sum('total_cents')),
            'Markup earned' => Money::format((int) (clone $confirmed)->sum('markup_cents')),
            'Customer wallets' => Money::format((int) User::query()->sum('balance_cents')),
        ];
    }

    #[Computed]
    public function pendingTopUps(): int
    {
        return TopUpRequest::query()->where('status', TopUpStatus::Pending)->count();
    }

    #[Computed]
    public function recent()
    {
        return Booking::query()->with('user')->latest()->limit(8)->get();
    }

    #[Computed]
    public function failed(): int
    {
        return Booking::query()->where('status', BookingStatus::Failed)->where('created_at', '>=', now()->subDays(7))->count();
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">Admin dashboard</flux:heading>
        <flux:text>Sales, wallets and the latest bookings.</flux:text>
    </div>

    @if (! app(\App\Services\Flights\FlightProvider::class)->isConfigured())
        <flux:callout variant="warning" icon="exclamation-triangle" heading="Flight search is not connected">
            <flux:callout.text>Add your Duffel API key under <flux:link :href="route('admin.settings')" wire:navigate>Pricing & API</flux:link> so visitors can search flights.</flux:callout.text>
        </flux:callout>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($this->stats as $label => $value)
            <flux:card>
                <flux:text>{{ $label }}</flux:text>
                <div class="mt-1 text-2xl font-bold">{{ $value }}</div>
            </flux:card>
        @endforeach
    </div>

    <div class="flex flex-wrap gap-3">
        <flux:button :href="route('admin.top-ups')" wire:navigate icon="banknotes">{{ $this->pendingTopUps }} top-ups to check</flux:button>
        @if ($this->failed)
            <flux:button :href="route('admin.bookings.index', ['status' => 'failed'])" wire:navigate icon="exclamation-circle">{{ $this->failed }} failed this week</flux:button>
        @endif
    </div>

    <flux:card>
        <flux:heading size="lg">Latest bookings</flux:heading>
        <flux:table class="mt-2">
            <flux:table.columns>
                <flux:table.column>Reference</flux:table.column>
                <flux:table.column>Customer</flux:table.column>
                <flux:table.column>Route</flux:table.column>
                <flux:table.column>Departs</flux:table.column>
                <flux:table.column>Total</flux:table.column>
                <flux:table.column>Status</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->recent as $booking)
                    <flux:table.row :key="$booking->id">
                        <flux:table.cell><flux:link :href="route('admin.bookings.show', $booking)" wire:navigate>{{ $booking->reference }}</flux:link></flux:table.cell>
                        <flux:table.cell>{{ $booking->user->name }}</flux:table.cell>
                        <flux:table.cell>{{ $booking->origin }} → {{ $booking->destination }}</flux:table.cell>
                        <flux:table.cell>{{ $booking->departs_at->format('j M, H:i') }}</flux:table.cell>
                        <flux:table.cell>{{ Money::format($booking->total_cents) }}</flux:table.cell>
                        <flux:table.cell><flux:badge size="sm" :color="$booking->status->color()">{{ $booking->status->label() }}</flux:badge></flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row><flux:table.cell colspan="6">No bookings yet.</flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
