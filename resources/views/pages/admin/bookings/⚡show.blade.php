<?php

use App\Models\Booking;
use App\Support\Money;
use Livewire\Component;

new class extends Component {
    public Booking $booking;

    public function mount(Booking $booking): void
    {
        $this->booking = $booking->load(['user', 'passengers', 'flights']);
    }

    public function render()
    {
        return $this->view()->title('Booking '.$this->booking->reference);
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl">Booking {{ $booking->reference }}</flux:heading>
            <flux:text>{{ $booking->origin }} → {{ $booking->destination }} · {{ $booking->departs_at->format('l j F Y, H:i') }}</flux:text>
        </div>
        <div class="flex gap-2">
            <flux:badge :color="$booking->status->color()">{{ $booking->status->label() }}</flux:badge>
            @if ($booking->isConfirmed())
                <flux:button size="sm" icon="arrow-down-tray" :href="route('bookings.ticket', $booking)">E-ticket PDF</flux:button>
            @endif
        </div>
    </div>

    @if ($booking->failure_reason)
        <flux:callout variant="danger" icon="exclamation-circle" :heading="$booking->failure_reason" />
    @endif

    <div class="grid gap-4 lg:grid-cols-3">
        <flux:card class="lg:col-span-2">
            <flux:heading size="lg">Flights</flux:heading>
            <div class="mt-3 grid gap-3">
                @foreach ($booking->segments as $segment)
                    <div class="rounded-lg border border-zinc-200 p-3 text-sm dark:border-zinc-700">
                        <div class="font-semibold">{{ $segment['carrier_code'] }} {{ $segment['flight_number'] }} · {{ $segment['carrier_name'] }}</div>
                        <div>{{ $segment['origin_name'] }} ({{ $segment['origin'] }}) {{ \Illuminate\Support\Carbon::parse($segment['departs_at'])->format('j M H:i') }} → {{ $segment['destination_name'] }} ({{ $segment['destination'] }}) {{ \Illuminate\Support\Carbon::parse($segment['arrives_at'])->format('j M H:i') }}</div>
                        @php $flight = $booking->flights->first(fn ($flight) => $flight->flight_number === $segment['flight_number'] && $flight->carrier_code === $segment['carrier_code']); @endphp
                        @if ($flight)
                            <div class="text-zinc-500">Seats: {{ $flight->seats_booked }} of {{ $flight->capacity() }} sold · {{ $flight->seatsLeft() }} left</div>
                        @endif
                    </div>
                @endforeach
            </div>

            <flux:heading size="lg" class="mt-6">Passengers</flux:heading>
            <flux:table class="mt-2">
                <flux:table.columns>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Gender</flux:table.column>
                    <flux:table.column>Born</flux:table.column>
                    <flux:table.column>Ticket</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($booking->passengers as $passenger)
                        <flux:table.row :key="$passenger->id">
                            <flux:table.cell>{{ $passenger->fullName() }}</flux:table.cell>
                            <flux:table.cell>{{ $passenger->gender === 'f' ? 'Female' : 'Male' }}</flux:table.cell>
                            <flux:table.cell>{{ $passenger->born_on->format('j M Y') }}</flux:table.cell>
                            <flux:table.cell>{{ $passenger->ticket_number ?? '—' }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>

        <div class="flex flex-col gap-4">
            <flux:card>
                <flux:heading size="lg">Money</flux:heading>
                <dl class="mt-3 grid grid-cols-2 gap-2 text-sm">
                    <dt class="text-zinc-500">Airline fare</dt><dd class="text-end">{{ $booking->provider_amount }} {{ $booking->provider_currency }}</dd>
                    <dt class="text-zinc-500">Fare in USD</dt><dd class="text-end">{{ Money::format($booking->fare_cents) }}</dd>
                    <dt class="text-zinc-500">Markup</dt><dd class="text-end">{{ Money::format($booking->markup_cents) }}</dd>
                    <dt class="font-semibold">Customer paid</dt><dd class="text-end font-semibold">{{ Money::format($booking->total_cents) }}</dd>
                </dl>
            </flux:card>
            <flux:card>
                <flux:heading size="lg">Customer</flux:heading>
                <div class="mt-3 text-sm">
                    <div class="font-semibold">{{ $booking->user->name }}</div>
                    <div>{{ $booking->contact_email }}</div>
                    <div>{{ $booking->contact_phone }}</div>
                    <div class="mt-2 text-zinc-500">Airline reference: {{ $booking->airline_reference ?? '—' }}</div>
                    <div class="text-zinc-500">Order: {{ $booking->provider_order_id ?? '—' }}</div>
                </div>
            </flux:card>
        </div>
    </div>
</div>
