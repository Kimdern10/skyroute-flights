<x-layouts::site current="home">
    <section class="hero">
        <div class="sky" aria-hidden="true">
            <div class="stars"></div>
            <span class="cloud cloud--1"></span>
            <span class="cloud cloud--2"></span>
            <span class="cloud cloud--3"></span>
            <svg class="flight-path" viewBox="0 0 640 300" fill="none">
                <path id="hero-route" class="trail" d="M10 280 C 160 250, 260 120, 630 30"/>
                <circle cx="10" cy="280" r="6" fill="#ffb27a"/>
                <circle cx="630" cy="30" r="6" fill="#fff"/>
                <g class="plane">
                    <path d="M-14 -3 L6 -3 L14 0 L6 3 L-14 3 Z M-2 -3 L-8 -14 L-3 -14 L6 -3 Z M-2 3 L-8 14 L-3 14 L6 3 Z M-14 -3 L-18 -9 L-15 -9 L-9 -3 Z M-14 3 L-18 9 L-15 9 L-9 3 Z"/>
                    <animateMotion dur="9s" repeatCount="indefinite" rotate="auto" keyPoints="0;1" keyTimes="0;1" calcMode="spline" keySplines=".45 0 .35 1">
                        <mpath href="#hero-route"/>
                    </animateMotion>
                </g>
            </svg>
        </div>
        <div class="container">
            <span class="eyebrow"><span class="dot"></span> Live fares from 300+ airlines</span>
            <h1>Fly anywhere. <em>Book in minutes.</em></h1>
            <p class="lead">Search real flights, see exactly how many seats are left, and pay from your SkyRoute wallet. Your e-ticket arrives by email straight away.</p>
        </div>
    </section>

    <div class="container">
        <x-search-form />
    </div>

    <section class="section">
        <div class="container">
            <div class="section-head" data-reveal>
                <div>
                    <span class="kicker">Popular right now</span>
                    <h2>Trending routes</h2>
                    <p>Tap a route to see the next flights and prices.</p>
                </div>
            </div>
            <div class="route-grid">
                @foreach ($routes as $index => [$from, $to])
                    <a class="route-card" href="{{ route('flights.index', ['from' => $from, 'to' => $to, 'date' => now()->addDays(14)->toDateString(), 'passengers' => 1]) }}" data-reveal style="--i: {{ $index }}">
                        <div class="codes">
                            <span>{{ $from }}</span>
                            <span class="line"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M21 16v-2l-8-5V3.5a1.5 1.5 0 0 0-3 0V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5z" transform="rotate(90 12 12)"/></svg></span>
                            <span>{{ $to }}</span>
                        </div>
                        <div class="cities"><span>{{ $cities[$from] ?? $from }}</span><span>{{ $cities[$to] ?? $to }}</span></div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section" style="padding-top: 0">
        <div class="container">
            <div class="section-head" data-reveal>
                <div>
                    <span class="kicker">Why SkyRoute</span>
                    <h2>Booking that just works</h2>
                </div>
            </div>
            <div class="features">
                <div class="feature" data-reveal style="--i: 0">
                    <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M3 12h4l3 8 4-16 3 8h4"/></svg></div>
                    <h3>Live airline fares</h3>
                    <p>Prices and times come straight from the airlines, so what you see is what you fly.</p>
                </div>
                <div class="feature" data-reveal style="--i: 1">
                    <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="3"/><path d="M9 9h6v6H9z"/></svg></div>
                    <h3>Real seat counts</h3>
                    <p>See "only 3 seats left" before you book. Sold-out flights are marked fully booked.</p>
                </div>
                <div class="feature" data-reveal style="--i: 2">
                    <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><rect x="3" y="6" width="18" height="13" rx="3"/><path d="M16 12.5h2M3 9h18"/></svg></div>
                    <h3>Simple wallet</h3>
                    <p>Top up once and pay for tickets in one tap. Every dollar shows on your statement.</p>
                </div>
                <div class="feature" data-reveal style="--i: 3">
                    <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h4"/></svg></div>
                    <h3>Instant e-ticket</h3>
                    <p>Get your booking reference and ticket number at once, plus a PDF by email.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section" style="padding-top: 0">
        <div class="container">
            <div class="section-head" data-reveal>
                <div>
                    <span class="kicker">How it works</span>
                    <h2>Four steps to take off</h2>
                </div>
            </div>
            <div class="steps">
                <div class="step" data-reveal style="--i: 0"><h3>Create your account</h3><p>Sign up free with your email.</p></div>
                <div class="step" data-reveal style="--i: 1"><h3>Top up your wallet</h3><p>Send money, tell us the reference, and we add it to your balance.</p></div>
                <div class="step" data-reveal style="--i: 2"><h3>Search and pick</h3><p>Compare times, stops and prices on live flights.</p></div>
                <div class="step" data-reveal style="--i: 3"><h3>Get your ticket</h3><p>Pay from your wallet and download the PDF e-ticket.</p></div>
            </div>
        </div>
    </section>
</x-layouts::site>
