@props([
    'from' => '',
    'to' => '',
    'date' => null,
    'passengers' => 1,
])

@php
    $airports = \Illuminate\Support\Facades\Cache::remember('airport-options', 3600, fn () => \App\Models\Airport::query()->orderBy('city')->get(['iata_code', 'name', 'city', 'country'])->toArray());
@endphp

<form {{ $attributes->merge(['class' => 'search-card']) }} method="GET" action="{{ route('flights.index') }}" role="search" aria-label="Search flights">
    <div class="search-grid">
        <div class="field">
            <label for="from">From</label>
            <input id="from" name="from" list="airport-list" value="{{ $from }}" placeholder="City or airport code" autocomplete="off" required maxlength="40" data-airport>
        </div>
        <button class="swap" type="button" data-swap aria-label="Swap from and to">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 4 3 8l4 4M3 8h14M17 20l4-4-4-4M21 16H7"/></svg>
        </button>
        <div class="field">
            <label for="to">To</label>
            <input id="to" name="to" list="airport-list" value="{{ $to }}" placeholder="Where to?" autocomplete="off" required maxlength="40" data-airport>
        </div>
        <div class="field field--date">
            <label for="date">Departure</label>
            <input id="date" name="date" type="date" value="{{ $date ?? now()->addDays(7)->toDateString() }}" min="{{ now()->toDateString() }}" max="{{ now()->addDays(330)->toDateString() }}" required>
        </div>
        <div class="field field--pax">
            <label for="passengers">Passengers</label>
            <select id="passengers" name="passengers">
                @foreach (range(1, 6) as $count)
                    <option value="{{ $count }}" @selected((int) $passengers === $count)>{{ $count }} {{ str('adult')->plural($count) }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn--primary" type="submit">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            Search flights
        </button>
    </div>
    <datalist id="airport-list">
        @foreach ($airports as $airport)
            <option value="{{ $airport['iata_code'] }}">{{ $airport['city'] }} · {{ $airport['name'] }}, {{ $airport['country'] }}</option>
        @endforeach
    </datalist>
</form>
