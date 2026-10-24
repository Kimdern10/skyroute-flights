<?php

use App\Models\Flight;
use App\Models\Setting;
use App\Support\Money;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Flights & seats')] class extends Component {
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: false)]
    public bool $past = false;

    public ?int $editingId = null;

    public string $seatsTotal = '';

    public string $priceOverride = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'past'], true)) {
            $this->resetPage();
        }
    }

    #[Computed]
    public function flights()
    {
        $search = strtoupper(trim($this->search));
        preg_match('/^([A-Z0-9]{2})\s*(\d{1,4})$/', $search, $flightCode);

        return Flight::query()
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('origin', $search)
                ->orWhere('destination', $search)
                ->orWhere('carrier_code', $search)
                ->when($flightCode, fn (Builder $query) => $query->orWhere(fn (Builder $query) => $query->where('carrier_code', $flightCode[1])->where('flight_number', $flightCode[2])))))
            ->when(! $this->past, fn (Builder $query) => $query->where('departs_at', '>=', now()->subDay()))
            ->orderBy('departs_at')
            ->paginate(25);
    }

    public function edit(int $id): void
    {
        $flight = Flight::query()->findOrFail($id);

        $this->editingId = $flight->id;
        $this->seatsTotal = (string) ($flight->seats_total ?? '');
        $this->priceOverride = $flight->price_override_cents !== null ? number_format($flight->price_override_cents / 100, 2, '.', '') : '';
        $this->resetValidation();

        Flux::modal('edit-flight')->show();
    }

    public function save(): void
    {
        $flight = Flight::query()->findOrFail($this->editingId);

        $this->validate([
            'seatsTotal' => ['nullable', 'integer', 'min:'.$flight->seats_booked, 'max:999'],
            'priceOverride' => ['nullable', 'numeric', 'min:1', 'max:50000'],
        ], [
            'seatsTotal.min' => "{$flight->seats_booked} seats are already sold, so the number cannot be lower.",
        ], ['seatsTotal' => 'seats for sale', 'priceOverride' => 'fixed price']);

        $flight->update([
            'seats_total' => $this->seatsTotal === '' ? null : (int) $this->seatsTotal,
            'price_override_cents' => $this->priceOverride === '' ? null : Money::toCents($this->priceOverride),
        ]);

        Flux::modal('edit-flight')->close();
        Flux::toast(variant: 'success', text: "{$flight->code()} saved.");
    }

    /**
     * Stop selling a flight: no seats beyond those already sold.
     */
    public function closeSales(int $id): void
    {
        $flight = Flight::query()->findOrFail($id);
        $flight->update(['seats_total' => $flight->seats_booked]);

        Flux::toast(text: "{$flight->code()} now shows as fully booked.");
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">Flights & seats</flux:heading>
        <flux:text>Flights are saved here once someone books them. Each sells {{ Setting::get('seats_per_flight') }} seats unless you set its own number. Set seats to the number sold to mark it fully booked.</flux:text>
    </div>

    <div class="flex flex-wrap items-center gap-4">
        <div class="w-full sm:w-72"><flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Airport, airline or flight (e.g. BA117)" /></div>
        <flux:switch wire:model.live="past" label="Show past flights" />
    </div>

    <flux:table :paginate="$this->flights">
        <flux:table.columns>
            <flux:table.column>Flight</flux:table.column>
            <flux:table.column>Route</flux:table.column>
            <flux:table.column>Departs</flux:table.column>
            <flux:table.column>Sold</flux:table.column>
            <flux:table.column>Seats left</flux:table.column>
            <flux:table.column>Fixed price</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->flights as $flight)
                <flux:table.row :key="$flight->id">
                    <flux:table.cell class="font-semibold">{{ $flight->code() }}<div class="text-xs font-normal text-zinc-500">{{ $flight->carrier_name }}</div></flux:table.cell>
                    <flux:table.cell>{{ $flight->origin }} → {{ $flight->destination }}</flux:table.cell>
                    <flux:table.cell>{{ $flight->departs_at->format('j M Y, H:i') }}</flux:table.cell>
                    <flux:table.cell>{{ $flight->seats_booked }} / {{ $flight->capacity() }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($flight->isFull())
                            <flux:badge size="sm" color="red">Fully booked</flux:badge>
                        @else
                            <flux:badge size="sm" :color="$flight->seatsLeft() <= 5 ? 'amber' : 'green'">{{ $flight->seatsLeft() }}</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>{{ $flight->price_override_cents !== null ? Money::format($flight->price_override_cents) : '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-end gap-2">
                            <flux:button size="sm" wire:click="edit({{ $flight->id }})">Edit</flux:button>
                            @unless ($flight->isFull())
                                <flux:button size="sm" variant="ghost" wire:click="closeSales({{ $flight->id }})" wire:confirm="Stop selling seats on {{ $flight->code() }}?">Close sales</flux:button>
                            @endunless
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row><flux:table.cell colspan="7">No flights yet. They appear here after the first booking.</flux:table.cell></flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="edit-flight" class="md:w-96">
        <form wire:submit="save" class="flex flex-col gap-5">
            <flux:heading size="lg">Edit flight</flux:heading>
            <flux:input wire:model="seatsTotal" type="number" min="0" label="Seats for sale" description="Leave empty to use the default ({{ Setting::get('seats_per_flight') }})." />
            <flux:input wire:model="priceOverride" type="number" step="0.01" label="Fixed price per passenger (USD)" description="Leave empty to use the airline fare plus your markup." icon="currency-dollar" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
