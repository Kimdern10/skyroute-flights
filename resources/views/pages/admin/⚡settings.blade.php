<?php

use App\Models\Setting;
use App\Services\Flights\FlightProvider;
use App\Services\Flights\FlightProviderException;
use App\Services\Flights\FlightSearch;
use App\Support\Money;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Pricing & API')] class extends Component {
    public string $business_name = '';

    public string $support_email = '';

    public string $support_phone = '';

    public string $markup_percent = '';

    public string $markup_fixed = '';

    public string $seats_per_flight = '';

    public string $currency_rates = '';

    public string $top_up_instructions = '';

    /** A new key; left empty to keep the saved one. */
    public string $duffel_token = '';

    public bool $hasToken = false;

    public function mount(): void
    {
        $values = Setting::values();

        foreach (['business_name', 'support_email', 'support_phone', 'markup_percent', 'seats_per_flight', 'currency_rates', 'top_up_instructions'] as $key) {
            $this->{$key} = $values[$key];
        }

        $this->markup_fixed = number_format(Setting::int('markup_fixed_cents') / 100, 2, '.', '');
        $this->hasToken = app(FlightProvider::class)->isConfigured();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'business_name' => ['required', 'string', 'max:60'],
            'support_email' => ['required', 'email', 'max:255'],
            'support_phone' => ['required', 'string', 'max:30'],
            'markup_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'markup_fixed' => ['required', 'numeric', 'min:0', 'max:1000'],
            'seats_per_flight' => ['required', 'integer', 'min:1', 'max:500'],
            'currency_rates' => ['nullable', 'string', 'max:2000'],
            'top_up_instructions' => ['required', 'string', 'max:2000'],
            'duffel_token' => ['nullable', 'string', 'starts_with:duffel_test_,duffel_live_', 'max:200'],
        ], [
            'duffel_token.starts_with' => 'A Duffel key starts with duffel_test_ or duffel_live_.',
        ]);

        $values = collect($validated)->except(['markup_fixed', 'duffel_token'])->all();
        $values['markup_fixed_cents'] = Money::toCents($validated['markup_fixed']);

        if (filled($validated['duffel_token'])) {
            $values['duffel_token'] = trim($validated['duffel_token']);
        }

        Setting::put($values);

        $this->duffel_token = '';
        $this->hasToken = app(FlightProvider::class)->isConfigured();

        Flux::toast(variant: 'success', text: 'Settings saved.');
    }

    public function removeToken(): void
    {
        Setting::put(['duffel_token' => '']);
        $this->hasToken = app(FlightProvider::class)->isConfigured();

        Flux::toast(text: 'The saved API key was removed.');
    }

    /**
     * Run a small real search so the owner knows the key works.
     */
    public function testConnection(FlightProvider $provider): void
    {
        try {
            $count = $provider->search(new FlightSearch('LHR', 'JFK', now()->addDays(30)->toDateString()))->count();
            Flux::toast(variant: 'success', text: "Connected. A test search found {$count} flights from London to New York.");
        } catch (FlightProviderException $exception) {
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }
    }
}; ?>

<div class="flex max-w-3xl flex-col gap-6">
    <div>
        <flux:heading size="xl">Pricing & API</flux:heading>
        <flux:text>Your selling prices, seats per flight and the flight API key.</flux:text>
    </div>

    <form wire:submit="save" class="flex flex-col gap-6">
        <flux:card class="flex flex-col gap-4">
            <flux:heading size="lg">Flight API (Duffel)</flux:heading>
            <flux:text>
                Create a free account at duffel.com, open <strong>Developers → Access tokens</strong>, and paste the token here.
                Test tokens (duffel_test_…) show test flights and issue test tickets. Live tokens (duffel_live_…) buy real tickets from your Duffel balance.
            </flux:text>
            <div class="flex items-center gap-2">
                @if ($hasToken)
                    <flux:badge color="green" icon="check-circle">Connected</flux:badge>
                    <flux:button size="sm" wire:click="testConnection">Test connection</flux:button>
                    @if (filled(Setting::get('duffel_token')))
                        <flux:button size="sm" variant="ghost" wire:click="removeToken" wire:confirm="Remove the saved key?">Remove saved key</flux:button>
                    @endif
                @else
                    <flux:badge color="amber" icon="exclamation-triangle">Not connected</flux:badge>
                @endif
            </div>
            <flux:input wire:model="duffel_token" type="password" label="{{ $hasToken ? 'Replace API key' : 'API key' }}" placeholder="duffel_test_..." autocomplete="off" viewable />
        </flux:card>

        <flux:card class="flex flex-col gap-4">
            <flux:heading size="lg">Prices</flux:heading>
            <flux:text>Customers pay the airline fare (in US dollars) plus your markup. You can also set a fixed price on one flight under Flights & seats.</flux:text>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="markup_percent" type="number" step="0.1" label="Markup (%)" />
                <flux:input wire:model="markup_fixed" type="number" step="0.01" label="Service fee per passenger (USD)" icon="currency-dollar" />
            </div>
            <flux:textarea wire:model="currency_rates" rows="4" label="Exchange rates to USD" description="One per line, e.g. GBP=1.27. Fares in a currency not listed here are hidden." />
            <flux:input wire:model="seats_per_flight" type="number" label="Seats you sell on each flight" description="A flight shows as fully booked once this many seats are sold." />
        </flux:card>

        <flux:card class="flex flex-col gap-4">
            <flux:heading size="lg">Business details</flux:heading>
            <flux:input wire:model="business_name" label="Business name" />
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="support_email" type="email" label="Support email" />
                <flux:input wire:model="support_phone" label="Support phone" />
            </div>
            <flux:textarea wire:model="top_up_instructions" rows="5" label="How customers pay you" description="Shown on the Wallet page: your bank details and what reference to use." />
        </flux:card>

        <div><flux:button type="submit" variant="primary">Save settings</flux:button></div>
    </form>
</div>
