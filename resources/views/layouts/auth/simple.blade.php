<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 antialiased dark:bg-zinc-950">
        <div class="grid min-h-svh lg:grid-cols-2">
            {{-- Brand side: sky, clouds and a plane flying across --}}
            <aside class="auth-sky relative hidden overflow-hidden p-10 text-white lg:flex lg:flex-col">
                <span class="auth-cloud auth-cloud--1" aria-hidden="true"></span>
                <span class="auth-cloud auth-cloud--2" aria-hidden="true"></span>
                <svg class="auth-route" viewBox="0 0 500 260" fill="none" aria-hidden="true">
                    <path id="auth-path" d="M10 240 C 140 220, 230 90, 490 30" stroke="rgb(255 255 255 / .45)" stroke-width="2" stroke-dasharray="2 10" stroke-linecap="round"/>
                    <circle cx="10" cy="240" r="6" fill="#ffb27a"/>
                    <circle cx="490" cy="30" r="6" fill="#fff"/>
                    <path fill="#fff" d="M-14 -3 L6 -3 L14 0 L6 3 L-14 3 Z M-2 -3 L-8 -14 L-3 -14 L6 -3 Z M-2 3 L-8 14 L-3 14 L6 3 Z M-14 -3 L-18 -9 L-15 -9 L-9 -3 Z M-14 3 L-18 9 L-15 9 L-9 3 Z">
                        <animateMotion dur="8s" repeatCount="indefinite" rotate="auto" calcMode="spline" keyPoints="0;1" keyTimes="0;1" keySplines=".45 0 .35 1">
                            <mpath href="#auth-path"/>
                        </animateMotion>
                    </path>
                </svg>

                <a href="{{ route('home') }}" class="relative flex items-center gap-3 text-xl font-extrabold">
                    <x-site-logo class="size-10" />
                    {{ \App\Models\Setting::get('business_name') }}
                </a>

                <div class="relative mt-auto max-w-md">
                    <h2 class="text-4xl font-extrabold leading-tight tracking-tight">Fly anywhere. <span class="text-[#ffb27a]">Book in minutes.</span></h2>
                    <p class="mt-4 text-lg text-blue-100">Live fares, real seat counts and your e-ticket by email, paid from your SkyRoute wallet.</p>
                    <ul class="mt-8 grid gap-3 text-sm text-blue-100">
                        <li class="flex items-center gap-2"><flux:icon.check-circle variant="mini" class="text-[#ffb27a]" /> See how many seats are left before you book</li>
                        <li class="flex items-center gap-2"><flux:icon.check-circle variant="mini" class="text-[#ffb27a]" /> Booking reference and ticket number straight away</li>
                        <li class="flex items-center gap-2"><flux:icon.check-circle variant="mini" class="text-[#ffb27a]" /> Download your e-ticket any time</li>
                    </ul>
                </div>
            </aside>

            {{-- Form side --}}
            <main class="flex flex-col p-6 md:p-10">
                <nav class="flex items-center justify-between gap-3" aria-label="Site">
                    <a href="{{ route('home') }}" class="flex items-center gap-2 text-lg font-extrabold text-zinc-900 lg:invisible dark:text-white">
                        <x-site-logo class="size-9" />
                        {{ \App\Models\Setting::get('business_name') }}
                    </a>
                    <div class="flex items-center gap-1">
                        <flux:button variant="ghost" size="sm" icon="home" :href="route('home')">Home</flux:button>
                        <flux:button variant="ghost" size="sm" icon="paper-airplane" :href="route('flights.index')">Flights</flux:button>
                    </div>
                </nav>

                <div class="flex flex-1 items-center justify-center py-10">
                    <div class="auth-card w-full max-w-sm rounded-2xl border border-zinc-200 bg-white p-6 shadow-xl shadow-blue-900/5 sm:p-8 dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex flex-col gap-6">
                            {{ $slot }}
                        </div>
                    </div>
                </div>
            </main>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
