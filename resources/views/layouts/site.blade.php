@props([
    'title' => null,
    'description' => null,
    'current' => null,
])

@php
    $site = \App\Models\Setting::values();
    $pageTitle = filled($title) ? $title.' · '.$site['business_name'] : $site['business_name'].' · Book cheap flights worldwide';
    $user = auth()->user();
    $links = ['flights' => ['Flights', route('flights.index')], 'my-booking' => ['My booking', route('my-booking')]];

    if ($user) {
        $links['trips'] = ['My trips', route('trips')];
    }
@endphp

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <title>{{ $pageTitle }}</title>
        <meta name="description" content="{{ $description ?: $site['tagline'] }}">
        <meta name="theme-color" content="#0b3d91">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="icon" href="/favicon.ico" sizes="any">
        <script>document.documentElement.classList.add('js'); try { var t = localStorage.getItem('sr-theme'); if (t === 'light' || t === 'dark') { document.documentElement.dataset.theme = t; } } catch (e) {}</script>
        @fonts('sora')
        @vite(['resources/css/site.css', 'resources/js/site.js'])
        @livewireStyles
    </head>
    <body>
        <a class="skip" href="#main">Skip to content</a>

        <header class="site-header">
            <div class="container header-row">
                <a class="brand" href="{{ route('home') }}" wire:navigate.hover aria-label="{{ $site['business_name'] }} home">
                    <x-site-logo />
                    <span>{{ $site['business_name'] }}</span>
                </a>
                <nav class="site-nav" aria-label="Main">
                    @foreach ($links as $key => [$label, $href])
                        <a href="{{ $href }}" wire:navigate.hover @if ($current === $key) aria-current="page" @endif>{{ $label }}</a>
                    @endforeach
                </nav>
                <div class="header-tools">
                    <button class="icon-btn" type="button" data-theme-toggle aria-label="Switch light or dark mode">
                        <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
                        <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                    </button>
                    @if ($user)
                        <a class="wallet-chip" href="{{ route('wallet') }}" title="Wallet balance">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="6" width="18" height="13" rx="3"/><path d="M16 12.5h2M3 9h18"/></svg>
                            {{ $user->balance() }}
                        </a>
                        <a class="btn btn--primary btn--sm" href="{{ $user->is_admin ? route('admin.dashboard') : route('dashboard') }}">{{ $user->is_admin ? 'Admin' : 'Account' }}</a>
                    @else
                        <a class="btn btn--ghost btn--sm hide-sm" href="{{ route('login') }}">Log in</a>
                        <a class="btn btn--primary btn--sm" href="{{ route('register') }}">Sign up</a>
                    @endif
                    <button class="icon-btn menu-toggle" type="button" data-menu-toggle aria-label="Open menu" aria-expanded="false" aria-controls="mobile-menu"><span></span><span></span></button>
                </div>
            </div>
        </header>

        <nav class="mobile-menu" id="mobile-menu" aria-label="Main (mobile)">
            @foreach ($links as [$label, $href])
                <a href="{{ $href }}" wire:navigate.hover>{{ $label }}</a>
            @endforeach
            @if ($user)
                <a href="{{ route('wallet') }}">Wallet · {{ $user->balance() }}</a>
            @else
                <a href="{{ route('login') }}">Log in</a>
            @endif
        </nav>

        <main id="main">
            {{ $slot }}
        </main>

        <footer class="site-footer">
            <div class="container footer-grid">
                <div>
                    <a class="brand" href="{{ route('home') }}" wire:navigate.hover><x-site-logo /><span>{{ $site['business_name'] }}</span></a>
                    <p>{{ $site['tagline'] }}</p>
                </div>
                <div>
                    <h4>Travel</h4>
                    <ul>
                        <li><a href="{{ route('flights.index') }}" wire:navigate.hover>Search flights</a></li>
                        <li><a href="{{ route('my-booking') }}" wire:navigate.hover>Find my booking</a></li>
                        <li><a href="{{ route('wallet') }}">Top up my wallet</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Help</h4>
                    <ul>
                        <li><a href="mailto:{{ $site['support_email'] }}">{{ $site['support_email'] }}</a></li>
                        <li><a href="tel:{{ preg_replace('/[^0-9+]/', '', $site['support_phone']) }}">{{ $site['support_phone'] }}</a></li>
                    </ul>
                </div>
            </div>
            <div class="container footer-bottom">© {{ now()->year }} {{ $site['business_name'] }}. Prices in US dollars. Fares come live from the airlines.</div>
        </footer>
        @livewireScripts
    </body>
</html>
