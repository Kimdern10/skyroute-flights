<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Bookings')] class extends Component {
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function bookings()
    {
        $search = trim($this->search);

        return Booking::query()
            ->with('user')
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('reference', $search)
                ->orWhere('airline_reference', $search)
                ->orWhere('contact_email', 'like', "%{$search}%")
                ->orWhereHas('passengers', fn (Builder $query) => $query->where('family_name', 'like', "%{$search}%"))))
            ->when(BookingStatus::tryFrom($this->status), fn (Builder $query, BookingStatus $status) => $query->where('status', $status))
            ->latest()
            ->paginate(20);
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">Bookings</flux:heading>
        <flux:text>Search by reference, airline reference, email or passenger last name.</flux:text>
    </div>

    <div class="flex flex-wrap gap-3">
        <div class="w-full sm:w-72"><flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search bookings" /></div>
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="status">
                <flux:select.option value="">All statuses</flux:select.option>
                @foreach (BookingStatus::cases() as $case)
                    <flux:select.option :value="$case->value">{{ $case->label() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <flux:table :paginate="$this->bookings">
        <flux:table.columns>
            <flux:table.column>Reference</flux:table.column>
            <flux:table.column>Customer</flux:table.column>
            <flux:table.column>Flight</flux:table.column>
            <flux:table.column>Departs</flux:table.column>
            <flux:table.column>Pax</flux:table.column>
            <flux:table.column>Total</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Booked</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->bookings as $booking)
                <flux:table.row :key="$booking->id">
                    <flux:table.cell><flux:link :href="route('admin.bookings.show', $booking)" wire:navigate>{{ $booking->reference }}</flux:link></flux:table.cell>
                    <flux:table.cell>{{ $booking->user->name }}<div class="text-xs text-zinc-500">{{ $booking->contact_email }}</div></flux:table.cell>
                    <flux:table.cell>{{ $booking->origin }} → {{ $booking->destination }}<div class="text-xs text-zinc-500">{{ $booking->flightNumbers() }}</div></flux:table.cell>
                    <flux:table.cell>{{ $booking->departs_at->format('j M Y, H:i') }}</flux:table.cell>
                    <flux:table.cell>{{ $booking->passengers_count }}</flux:table.cell>
                    <flux:table.cell>{{ Money::format($booking->total_cents) }}</flux:table.cell>
                    <flux:table.cell><flux:badge size="sm" :color="$booking->status->color()">{{ $booking->status->label() }}</flux:badge></flux:table.cell>
                    <flux:table.cell>{{ $booking->created_at->format('j M, H:i') }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row><flux:table.cell colspan="8">No bookings match.</flux:table.cell></flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
