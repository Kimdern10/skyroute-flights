<?php

use App\Models\Airport;
use App\Services\Flights\FlightOffer;
use App\Services\Flights\FlightProvider;
use App\Services\Flights\FlightProviderException;
use App\Services\Flights\FlightSearch;
use App\Services\Pricing;
use App\Services\SeatInventory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component {
    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    #[Url(except: '')]
    public string $date = '';

    #[Url(except: 1)]
    public int $passengers = 1;

    #[Url(except: 'price')]
    public string $sort = 'price';

    #[Url(except: false)]
    public bool $direct = false;

    /** True once the page is on screen, so the slow airline search runs after the first paint. */
    public bool $ready = false;

    public ?string $error = null;

    public function mount(): void
    {
        $this->from = $this->airportCode($this->from);
        $this->to = $this->airportCode($this->to);
        $this->passengers = max(1, min(6, $this->passengers));

        if ($this->date === '' || ! strtotime($this->date) || Carbon::parse($this->date)->isBefore(today())) {
            $this->date = today()->addDays(7)->toDateString();
        }
    }

    public function load(): void
    {
        $this->ready = true;
    }

    public function sortBy(string $sort): void
    {
        $this->sort = in_array($sort, ['price', 'departure', 'duration'], true) ? $sort : 'price';
    }

    #[Computed]
    public function canSearch(): bool
    {
        return strlen($this->from) === 3 && strlen($this->to) === 3 && $this->from !== $this->to;
    }

    /**
     * Offers with their seats left and our price.
     *
     * @return Collection<int, array{offer: FlightOffer, seats_left: int, price: array{fare_cents: int, markup_cents: int, total_cents: int}|null}>
     */
    #[Computed]
    public function results(): Collection
    {
        $this->error = null;

        if (! $this->ready || ! $this->canSearch) {
            return collect();
        }

        $search = new FlightSearch($this->from, $this->to, $this->date, $this->passengers);
        $provider = app(FlightProvider::class);

        try {
            $offers = collect(Cache::remember(
                $search->cacheKey(),
                now()->addMinutes(10),
                fn (): array => $provider->search($search)->map(fn (FlightOffer $offer): array => $offer->toArray())->all(),
            ))->map(FlightOffer::fromArray(...));
        } catch (FlightProviderException $exception) {
            $this->error = $exception->getMessage();

            return collect();
        }

        $inventory = app(SeatInventory::class);
        $pricing = app(Pricing::class);
        $flights = $inventory->flightsFor($offers);

        $results = $offers
            ->when($this->direct, fn (Collection $offers): Collection => $offers->filter(fn (FlightOffer $offer): bool => $offer->stops() === 0))
            ->map(fn (FlightOffer $offer): array => [
                'offer' => $offer,
                'seats_left' => $inventory->seatsLeft($offer, $flights),
                'price' => $pricing->quote($offer, $this->passengers, $flights->get(SeatInventory::keyFor($offer->first()))),
            ])
            ->filter(fn (array $result): bool => $result['price'] !== null);

        $sorted = match ($this->sort) {
            'departure' => $results->sortBy(fn (array $result): int => $result['offer']->first()->departsAt->getTimestamp()),
            'duration' => $results->sortBy(fn (array $result): int => $result['offer']->durationMinutes),
            default => $results->sortBy(fn (array $result): int => $result['price']['total_cents']),
        };

        return $sorted->values();
    }

    /**
     * @return list<Carbon>
     */
    #[Computed]
    public function nearbyDates(): array
    {
        $chosen = Carbon::parse($this->date);
        $start = $chosen->copy()->subDays(3)->max(today());

        return array_map(fn (int $day): Carbon => $start->copy()->addDays($day), range(0, 6));
    }

    public function render()
    {
        $title = $this->canSearch
            ? Airport::cityFor($this->from).' to '.Airport::cityFor($this->to)
            : 'Search flights';

        return $this->view()->layout('layouts::site', ['current' => 'flights'])->title($title);
    }

    /**
     * Turn "lagos", "LOS" or "Lagos (LOS)" into an airport code.
     */
    private function airportCode(string $value): string
    {
        $value = trim($value);

        if (preg_match('/\(([A-Za-z]{3})\)\s*$/', $value, $match)) {
            return strtoupper($match[1]);
        }

        if (preg_match('/^[A-Za-z]{3}$/', $value)) {
            return strtoupper($value);
        }

        if ($value === '') {
            return '';
        }

        return Airport::query()->where('city', 'like', $value.'%')->orWhere('name', 'like', $value.'%')->value('iata_code') ?? '';
    }
}; ?>

<div>
    <section class="page-hero">
        <div class="container">
            @if ($this->canSearch)
                <h1>{{ Airport::cityFor($from) }} <span aria-hidden="true">→</span> {{ Airport::cityFor($to) }}</h1>
                <p>{{ Carbon::parse($date)->format('l j F Y') }} · {{ $passengers }} {{ str('passenger')->plural($passengers) }}</p>
            @else
                <h1>Where are you flying?</h1>
                <p>Choose where from, where to and the day you want to fly.</p>
            @endif
        </div>
    </section>

    <div class="container">
        <x-search-form :from="$from" :to="$to" :date="$date" :passengers="$passengers" />

        @if ($this->canSearch)
            <nav class="date-strip" aria-label="Nearby dates" style="margin-top: 18px">
                @foreach ($this->nearbyDates as $day)
                    <a href="{{ route('flights.index', ['from' => $from, 'to' => $to, 'date' => $day->toDateString(), 'passengers' => $passengers]) }}" wire:navigate @if ($day->toDateString() === $date) aria-current="date" @endif>
                        {{ $day->format('D') }}
                        <strong>{{ $day->format('j M') }}</strong>
                    </a>
                @endforeach
            </nav>

            <div wire:init="load">
                @php $results = $this->results; @endphp
                @if (! $ready)
                    <div class="loading-flight" role="status">
                        <div class="track"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M21 16v-2l-8-5V3.5a1.5 1.5 0 0 0-3 0V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5z" transform="rotate(90 12 12)"/></svg></div>
                        Asking the airlines for the best fares…
                    </div>
                    <div class="offers">
                        @foreach (range(1, 3) as $row)
                            <div class="skeleton"></div>
                        @endforeach
                    </div>
                @elseif ($this->error)
                    <div class="notice" style="margin: 24px 0 60px">
                        <h2>We couldn't load flights</h2>
                        <p>{{ $this->error }}</p>
                        <button class="btn btn--blue" type="button" wire:click="$refresh">Try again</button>
                    </div>
                @elseif ($results->isEmpty())
                    <div class="notice" style="margin: 24px 0 60px">
                        <h2>No flights on this day</h2>
                        <p>There are no bookable flights from {{ Airport::cityFor($from) }} to {{ Airport::cityFor($to) }} on {{ Carbon::parse($date)->format('j F') }}{{ $direct ? ' without stops' : '' }}. Try one of the nearby dates above.</p>
                        @if ($direct)
                            <button class="btn btn--blue" type="button" wire:click="$set('direct', false)">Show flights with stops</button>
                        @endif
                    </div>
                @else
                    <div class="toolbar">
                        <span class="count">{{ $results->count() }} {{ str('flight')->plural($results->count()) }} found</span>
                        <div class="chips" role="group" aria-label="Sort and filter">
                            <button class="chip" type="button" wire:click="sortBy('price')" aria-pressed="{{ $sort === 'price' ? 'true' : 'false' }}">Cheapest</button>
                            <button class="chip" type="button" wire:click="sortBy('departure')" aria-pressed="{{ $sort === 'departure' ? 'true' : 'false' }}">Earliest</button>
                            <button class="chip" type="button" wire:click="sortBy('duration')" aria-pressed="{{ $sort === 'duration' ? 'true' : 'false' }}">Fastest</button>
                            <button class="chip" type="button" wire:click="$toggle('direct')" aria-pressed="{{ $direct ? 'true' : 'false' }}">Direct only</button>
                        </div>
                    </div>

                    <div class="offers" wire:loading.class="is-loading">
                        @foreach ($results as $index => ['offer' => $offer, 'seats_left' => $seatsLeft, 'price' => $price])
                            @php
                                $full = $seatsLeft === 0;
                                $tooFew = ! $full && $seatsLeft < $passengers;
                                $days = (int) $offer->first()->departsAt->startOfDay()->diffInDays($offer->last()->arrivesAt->startOfDay());
                            @endphp
                            <article @class(['offer', 'is-full' => $full || $tooFew]) style="--i: {{ $index }}" wire:key="offer-{{ $offer->id }}" data-test="offer">
                                <div class="airline">
                                    @if ($offer->airlineLogo)
                                        <img src="{{ $offer->airlineLogo }}" alt="" width="42" height="42" loading="lazy">
                                    @else
                                        <span class="logo-fallback">{{ $offer->airlineCode }}</span>
                                    @endif
                                    <div style="min-width: 0">
                                        <strong>{{ $offer->airlineName }}</strong>
                                        <span>{{ collect($offer->segments)->map(fn ($s) => $s->carrierCode.' '.$s->flightNumber)->implode(' · ') }}</span>
                                    </div>
                                </div>

                                <div class="timeline">
                                    <div>
                                        <div class="time">{{ $offer->first()->departsAt->format('H:i') }}</div>
                                        <div class="ap">{{ $offer->first()->origin }}</div>
                                    </div>
                                    <div class="mid">
                                        {{ $offer->duration() }}
                                        <div class="bar">
                                            @foreach (array_slice($offer->segments, 1) as $stopIndex => $stop)
                                                <i style="left: {{ (($stopIndex + 1) / count($offer->segments)) * 100 }}%"></i>
                                            @endforeach
                                        </div>
                                        {{ $offer->stops() === 0 ? 'Direct' : $offer->stops().' '.str('stop')->plural($offer->stops()).' · '.collect(array_slice($offer->segments, 1))->pluck('origin')->implode(', ') }}
                                    </div>
                                    <div class="end">
                                        <div class="time">{{ $offer->last()->arrivesAt->format('H:i') }}@if ($days > 0)<span class="plus">+{{ $days }}</span>@endif</div>
                                        <div class="ap">{{ $offer->last()->destination }}</div>
                                    </div>
                                </div>

                                <div class="price">
                                    @if ($full)
                                        <span class="badge badge--full" data-test="fully-booked">Fully booked</span>
                                    @elseif ($seatsLeft <= 5)
                                        <span class="badge badge--low">Only {{ $seatsLeft }} {{ str('seat')->plural($seatsLeft) }} left</span>
                                    @else
                                        <span class="badge badge--ok">{{ $offer->cabin }}</span>
                                    @endif
                                    <div class="amount">{{ \App\Support\Money::format($price['total_cents']) }}</div>
                                    <div class="per">{{ $passengers > 1 ? 'total for '.$passengers.' passengers' : 'per passenger' }}</div>
                                    @if ($full)
                                        <button class="btn btn--ghost btn--sm" type="button" disabled>No seats available</button>
                                    @elseif ($tooFew)
                                        <button class="btn btn--ghost btn--sm" type="button" disabled>Not enough seats</button>
                                    @else
                                        <a class="btn btn--primary btn--sm" href="{{ route('flights.book', $offer->id) }}">Book now</a>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        @else
            <div style="height: 60px"></div>
        @endif
    </div>
</div>
