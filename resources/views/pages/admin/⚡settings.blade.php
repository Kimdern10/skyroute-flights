<?php

use App\Models\Setting;
use App\Services\Flights\FlightProvider;
use App\Services\Flights\FlightProviderException;
use App\Services\Flights\FlightSearch;
use App\Services\Paystack;
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

    /** A new Paystack secret key; left empty to keep the saved one. */
    public string $paystack_secret = '';

    public string $paystack_currency = 'NGN';

    public string $paystack_rate = '';

    public bool $hasPaystack = false;

    public string $privacy_policy = '';

    public string $terms = '';

    /**
     * Settings edited on this page as plain text.
     *
     * @var list<string>
     */
    private const array TEXT_KEYS = [
        'business_name', 'support_email', 'support_phone', 'markup_percent', 'seats_per_flight', 'currency_rates',
        'top_up_instructions', 'paystack_currency', 'paystack_rate', 'privacy_policy', 'terms',
    ];

    public function mount(): void
    {
        $values = Setting::values();

        foreach (self::TEXT_KEYS as $key) {
            $this->{$key} = $values[$key];
        }

        // Show the starter text so the owner edits it, rather than an empty box.
        $this->privacy_policy = Setting::privacyPolicy();
        $this->terms = Setting::terms();

        $this->markup_fixed = number_format(Setting::int('markup_fixed_cents') / 100, 2, '.', '');
        $this->hasToken = app(FlightProvider::class)->isConfigured();
        $this->hasPaystack = app(Paystack::class)->isConfigured();
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
            'paystack_secret' => ['nullable', 'string', 'starts_with:sk_test_,sk_live_', 'max:200'],
            'paystack_currency' => ['required', 'in:NGN,USD,GHS,KES,ZAR'],
            'paystack_rate' => ['required_unless:paystack_currency,USD', 'nullable', 'numeric', 'min:0.0001', 'max:100000'],
            'privacy_policy' => ['required', 'string', 'max:20000'],
            'terms' => ['required', 'string', 'max:20000'],
        ], [
            'duffel_token.starts_with' => 'A Duffel key starts with duffel_test_ or duffel_live_.',
            'paystack_secret.starts_with' => 'A Paystack secret key starts with sk_test_ or sk_live_ (not the public key, which starts with pk_).',
        ], ['paystack_rate' => 'exchange rate']);

        $values = collect($validated)->except(['markup_fixed', 'duffel_token', 'paystack_secret'])->all();
        $values['markup_fixed_cents'] = Money::toCents($validated['markup_fixed']);

        foreach (['duffel_token', 'paystack_secret'] as $secret) {
            if (filled($validated[$secret])) {
                $values[$secret] = trim($validated[$secret]);
            }
        }

        Setting::put($values);

        $this->duffel_token = '';
        $this->paystack_secret = '';
        $this->hasToken = app(FlightProvider::class)->isConfigured();
        $this->hasPaystack = app(Paystack::class)->isConfigured();

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
        <flux:text>Your selling prices, seats per flight, API keys, card payments and legal pages.</flux:text>
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

        <flux:card class="flex flex-col gap-4">
            <flux:heading size="lg">Card payments (Paystack)</flux:heading>
            <flux:text>
                Lets customers top up their wallet by card. In your Paystack dashboard open <strong>Settings → API Keys &amp; Webhooks</strong>,
                copy the <strong>secret key</strong> (sk_test_… for testing) and paste it here. Then set the webhook URL there to
                <code class="rounded bg-zinc-100 px-1 dark:bg-zinc-800">{{ route('paystack.webhook') }}</code>.
            </flux:text>
            <div>
                @if ($hasPaystack)
                    <flux:badge color="green" icon="check-circle">Card payments on</flux:badge>
                @else
                    <flux:badge color="amber" icon="exclamation-triangle">Card payments off</flux:badge>
                @endif
            </div>
            <flux:input wire:model="paystack_secret" type="password" label="{{ $hasPaystack ? 'Replace secret key' : 'Secret key' }}" placeholder="sk_test_..." autocomplete="off" viewable />
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model.live="paystack_currency" label="Charge cards in">
                    <flux:select.option value="NGN">Naira (NGN)</flux:select.option>
                    <flux:select.option value="USD">US dollars (USD)</flux:select.option>
                    <flux:select.option value="GHS">Ghana cedis (GHS)</flux:select.option>
                    <flux:select.option value="KES">Kenyan shillings (KES)</flux:select.option>
                    <flux:select.option value="ZAR">South African rand (ZAR)</flux:select.option>
                </flux:select>
                @if ($paystack_currency !== 'USD')
                    <flux:input wire:model="paystack_rate" type="number" step="0.01" label="{{ $paystack_currency }} per $1" description="Used to work out the card charge. Update it when the rate changes." />
                @endif
            </div>
        </flux:card>

        <flux:card class="flex flex-col gap-4">
            <flux:heading size="lg">Privacy policy and terms</flux:heading>
            <flux:text>Starter text is filled in for you. Read it, change anything that doesn't match how you work, and ideally have a lawyer check it. Use ## for headings and - for lists.</flux:text>
            <flux:textarea wire:model="privacy_policy" rows="10" label="Privacy policy" />
            <flux:textarea wire:model="terms" rows="10" label="Terms of use" />
        </flux:card>

        <div><flux:button type="submit" variant="primary">Save settings</flux:button></div>
    </form>
</div>
