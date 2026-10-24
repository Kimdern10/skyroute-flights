<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('My trips')] class extends Component {
    use WithPagination;

    #[Computed]
    public function bookings()
    {
        return Auth::user()->bookings()->latest()->paginate(15);
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">My trips</flux:heading>
        <flux:text>Every flight you've booked. Open one to see the ticket or download the PDF.</flux:text>
    </div>

    <flux:table :paginate="$this->bookings">
        <flux:table.columns>
            <flux:table.column>Reference</flux:table.column>
            <flux:table.column>Route</flux:table.column>
            <flux:table.column>Departs</flux:table.column>
            <flux:table.column>Passengers</flux:table.column>
            <flux:table.column>Paid</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->bookings as $booking)
                <flux:table.row :key="$booking->id">
                    <flux:table.cell class="font-semibold">{{ $booking->reference }}</flux:table.cell>
                    <flux:table.cell>{{ $booking->origin }} → {{ $booking->destination }}</flux:table.cell>
                    <flux:table.cell>{{ $booking->departs_at->format('j M Y, H:i') }}</flux:table.cell>
                    <flux:table.cell>{{ $booking->passengers_count }}</flux:table.cell>
                    <flux:table.cell>{{ \App\Support\Money::format($booking->total_cents) }}</flux:table.cell>
                    <flux:table.cell><flux:badge size="sm" :color="$booking->status->color()">{{ $booking->status->label() }}</flux:badge></flux:table.cell>
                    <flux:table.cell>
                        <flux:button size="sm" :href="route('bookings.show', $booking)">View</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7">No bookings yet.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
