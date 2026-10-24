<?php

use App\Actions\BookFlight;
use App\Actions\BookingException;
use App\Models\Airport;
use App\Models\Passenger;
use App\Services\Flights\FlightOffer;
use App\Services\Flights\FlightProvider;
use App\Services\Flights\FlightProviderException;
use App\Services\Pricing;
use App\Services\SeatInventory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component {
    #[Locked]
    public string $offerId = '';

    /** @var list<array{title: string, given_name: string, family_name: string, gender: string, born_on: string}> */
    public array $passengers = [];

    public string $email = '';

    public string $phone = '';

    public ?string $error = null;

    public function mount(string $offer): void
    {
        $this->offerId = $offer;
        $user = Auth::user();
        $this->email = $user->email;
        $this->phone = (string) $user->phone;

        $count = max(1, count($this->offer?->passengerIds ?? []));
        [$given, $family] = array_pad(explode(' ', $user->name, 2), 2, '');

        $this->passengers = array_map(fn (int $index): array => [
            'title' => 'mr',
            'given_name' => $index === 0 ? $given : '',
            'family_name' => $index === 0 ? $family : '',
            'gender' => 'm',
            'born_on' => '',
        ], range(0, $count - 1));
    }

    /**
     * The latest fare from the airline, kept for a few minutes while the form is filled in.
     */
    #[Computed]
    public function offer(): ?FlightOffer
    {
        try {
            $offer = Cache::remember('flight-offer:'.$this->offerId, now()->addMinutes(5), fn (): ?array => app(FlightProvider::class)->find($this->offerId)?->toArray());

            return $offer ? FlightOffer::fromArray($offer) : null;
        } catch (FlightProviderException $exception) {
            $this->error ??= $exception->getMessage();

            return null;
        }
    }

    #[Computed]
    public function seatsLeft(): int
    {
        $inventory = app(SeatInventory::class);

        return $inventory->seatsLeft($this->offer, $inventory->flightsFor([$this->offer]));
    }

    /**
     * @return array{fare_cents: int, markup_cents: int, total_cents: int}|null
     */
    #[Computed]
    public function price(): ?array
    {
        $inventory = app(SeatInventory::class);
        $flights = $inventory->flightsFor([$this->offer]);

        return app(Pricing::class)->quote($this->offer, count($this->passengers), $flights->get(SeatInventory::keyFor($this->offer->first())));
    }

    public function book(BookFlight $bookFlight): void
    {
        $this->error = null;

        $validated = $this->validate([
            'passengers' => ['required', 'array', 'min:1', 'max:6'],
            'passengers.*.title' => ['required', Rule::in(array_keys(Passenger::TITLES))],
            'passengers.*.given_name' => ['required', 'string', 'max:60', "regex:/^[\pL' -]+$/u"],
            'passengers.*.family_name' => ['required', 'string', 'max:60', "regex:/^[\pL' -]+$/u"],
            'passengers.*.gender' => ['required', Rule::in(['m', 'f'])],
            'passengers.*.born_on' => ['required', 'date', 'before:today', 'after:1900-01-01'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^\+[1-9][0-9]{6,14}$/'],
        ], [
            'phone.regex' => 'Enter the phone number with the country code and no spaces, e.g. +14155550123.',
            'passengers.*.given_name.regex' => 'Use letters only, as written in the passport.',
            'passengers.*.family_name.regex' => 'Use letters only, as written in the passport.',
        ], [
            'passengers.*.given_name' => 'first name',
            'passengers.*.family_name' => 'last name',
            'passengers.*.born_on' => 'date of birth',
        ]);

        // Price and seats must be checked against the airline's latest answer, not the saved copy.
        Cache::forget('flight-offer:'.$this->offerId);
        unset($this->offer, $this->price, $this->seatsLeft);

        if (! $this->offer) {
            $this->error ??= 'This fare has expired. Please search again.';

            return;
        }

        $user = Auth::user();

        if (blank($user->phone)) {
            $user->forceFill(['phone' => $validated['phone']])->save();
        }

        try {
            $booking = $bookFlight->handle($user, $this->offer, $validated['passengers'], $validated['email'], $validated['phone']);
        } catch (BookingException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        session()->flash('booked', $booking->reference);

        $this->redirectRoute('bookings.show', $booking);
    }

    public function render()
    {
        return $this->view()->layout('layouts::site', ['current' => 'flights'])->title('Passenger details');
    }
}; ?>

<div>
    <section class="page-hero">
        <div class="container">
            @if ($this->offer)
                <h1>{{ Airport::cityFor($this->offer->first()->origin) }} → {{ Airport::cityFor($this->offer->last()->destination) }}</h1>
                <p>{{ $this->offer->first()->departsAt->format('l j F Y, H:i') }} · {{ $this->offer->airlineName }}</p>
            @else
                <h1>Book your flight</h1>
            @endif
        </div>
    </section>

    <div class="container">
        @if (! $this->offer)
            <div class="notice" style="margin-bottom: 60px">
                <h2>This fare is no longer available</h2>
                <p>{{ $this->error ?? 'Airline fares only last a short time. Please search again to see the latest prices.' }}</p>
                <a class="btn btn--primary" href="{{ route('flights.index') }}">Search again</a>
            </div>
        @else
            @php
                $price = $this->price;
                $seatsLeft = $this->seatsLeft;
                $balance = auth()->user()->balance_cents;
                $short = $price ? max(0, $price['total_cents'] - $balance) : 0;
            @endphp
            <form class="book-grid" wire:submit="book">
                <div>
                    @if ($this->error)
                        <div class="alert" role="alert" data-test="booking-error">{{ $this->error }}</div>
                    @endif

                    @foreach ($passengers as $index => $passenger)
                        <section class="panel" wire:key="pax-{{ $index }}">
                            <h2>Passenger {{ $index + 1 }} <span class="muted small">· adult</span></h2>
                            <p class="muted small" style="margin-top: -6px">Type the names exactly as they are in the passport.</p>
                            <div class="form-grid form-grid--4">
                                <div class="field">
                                    <label for="title-{{ $index }}">Title</label>
                                    <select id="title-{{ $index }}" wire:model="passengers.{{ $index }}.title">
                                        @foreach (Passenger::TITLES as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="field">
                                    <label for="given-{{ $index }}">First name</label>
                                    <input id="given-{{ $index }}" wire:model="passengers.{{ $index }}.given_name" autocomplete="{{ $index === 0 ? 'given-name' : 'off' }}">
                                    @error("passengers.$index.given_name") <span class="error">{{ $message }}</span> @enderror
                                </div>
                                <div class="field">
                                    <label for="family-{{ $index }}">Last name</label>
                                    <input id="family-{{ $index }}" wire:model="passengers.{{ $index }}.family_name" autocomplete="{{ $index === 0 ? 'family-name' : 'off' }}">
                                    @error("passengers.$index.family_name") <span class="error">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="form-grid" style="margin-top: 14px">
                                <div class="field">
                                    <label for="gender-{{ $index }}">Gender</label>
                                    <select id="gender-{{ $index }}" wire:model="passengers.{{ $index }}.gender">
                                        <option value="m">Male</option>
                                        <option value="f">Female</option>
                                    </select>
                                </div>
                                <div class="field">
                                    <label for="born-{{ $index }}">Date of birth</label>
                                    <input id="born-{{ $index }}" type="date" wire:model="passengers.{{ $index }}.born_on" max="{{ now()->subDay()->toDateString() }}">
                                    @error("passengers.$index.born_on") <span class="error">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </section>
                    @endforeach

                    <section class="panel">
                        <h2>Contact details</h2>
                        <p class="muted small" style="margin-top: -6px">We send the e-ticket here, and the airline uses them if the flight changes.</p>
                        <div class="form-grid">
                            <div class="field">
                                <label for="email">Email</label>
                                <input id="email" type="email" wire:model="email" autocomplete="email">
                                @error('email') <span class="error">{{ $message }}</span> @enderror
                            </div>
                            <div class="field">
                                <label for="phone">Phone (with country code)</label>
                                <input id="phone" type="tel" wire:model="phone" placeholder="+14155550123" autocomplete="tel">
                                @error('phone') <span class="error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </section>
                </div>

                <aside class="panel summary" aria-label="Trip summary">
                    <h2>Your trip</h2>
                    @foreach ($this->offer->segments as $segment)
                        <div class="seg">
                            <strong>{{ $segment->departsAt->format('H:i') }}</strong><span>{{ $segment->originName }} ({{ $segment->origin }})</span>
                            <strong>{{ $segment->arrivesAt->format('H:i') }}</strong><span>{{ $segment->destinationName }} ({{ $segment->destination }})</span>
                            <span></span><span class="muted small">{{ $segment->carrierName }} {{ $segment->carrierCode }} {{ $segment->flightNumber }}{{ $segment->aircraft ? ' · '.$segment->aircraft : '' }}</span>
                        </div>
                    @endforeach

                    @if ($price)
                        <div class="row"><span>{{ count($passengers) }} × adult · {{ $this->offer->cabin }}</span><span></span></div>
                        <div class="row"><span>Total</span><span class="total" data-test="total">{{ \App\Support\Money::format($price['total_cents']) }}</span></div>
                        <div class="row"><span class="muted">Your wallet</span><span>{{ \App\Support\Money::format($balance) }}</span></div>
                    @endif

                    @if ($seatsLeft === 0)
                        <span class="badge badge--full">Fully booked</span>
                        <button class="btn btn--ghost btn--block" type="button" disabled style="margin-top: 12px">No seats available</button>
                    @elseif (! $price)
                        <p class="muted">This fare cannot be priced right now.</p>
                    @elseif ($short > 0)
                        <div class="alert" style="margin-top: 12px">You need {{ \App\Support\Money::format($short) }} more in your wallet to book this flight.</div>
                        <a class="btn btn--primary btn--block" href="{{ route('wallet') }}">Top up my wallet</a>
                    @else
                        @if ($seatsLeft <= 5)
                            <span class="badge badge--low">Only {{ $seatsLeft }} {{ str('seat')->plural($seatsLeft) }} left</span>
                        @endif
                        <button class="btn btn--primary btn--block" type="submit" style="margin-top: 12px" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="book">Pay {{ \App\Support\Money::format($price['total_cents']) }} and book</span>
                            <span wire:loading wire:target="book">Issuing your ticket…</span>
                        </button>
                        <p class="muted small" style="margin: 10px 0 0">If the airline cannot issue the ticket, the money goes straight back to your wallet.</p>
                    @endif
                </aside>
            </form>
        @endif
    </div>
</div>
