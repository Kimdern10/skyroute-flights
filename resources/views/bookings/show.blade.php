<x-layouts::site :title="'Booking '.$booking->reference">
    @if ($justBooked)
        <div class="confetti" data-confetti aria-hidden="true"></div>
    @endif

    <section class="page-hero">
        <div class="container">
            <h1>{{ $booking->isConfirmed() ? 'You\'re all set!' : 'Booking '.$booking->reference }}</h1>
            <p>{{ $booking->isConfirmed() ? 'Your ticket is issued. We\'ve emailed the e-ticket to '.$booking->contact_email.'.' : 'Status: '.$booking->status->label() }}</p>
        </div>
    </section>

    <div class="container" style="padding-bottom: 60px">
        <article class="pass" aria-label="E-ticket summary">
            <div class="pass-top">
                <div>
                    <div class="label" style="color: #c9d7f2">Booking reference</div>
                    <div class="ref" data-test="reference">{{ $booking->reference }}</div>
                </div>
                <div style="text-align: right">
                    <div class="label" style="color: #c9d7f2">Airline reference</div>
                    <div style="font-size: 20px; font-weight: 700">{{ $booking->airline_reference ?: '—' }}</div>
                </div>
            </div>

            <div class="pass-route">
                <div>
                    <div class="code">{{ $booking->origin }}</div>
                    <div>{{ \App\Models\Airport::cityFor($booking->origin) }}</div>
                    <div class="muted small">{{ $booking->departs_at->format('D j M, H:i') }}</div>
                </div>
                <div class="fly" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 16v-2l-8-5V3.5a1.5 1.5 0 0 0-3 0V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5z" transform="rotate(90 12 12)"/></svg>
                </div>
                <div class="end">
                    <div class="code">{{ $booking->destination }}</div>
                    <div>{{ \App\Models\Airport::cityFor($booking->destination) }}</div>
                    <div class="muted small">{{ $booking->arrives_at->format('D j M, H:i') }}</div>
                </div>
            </div>

            <div class="pass-perf"></div>

            <div class="pass-body">
                <div class="pass-facts">
                    <div><span class="label">Airline</span><strong>{{ $booking->carrier_name }}</strong></div>
                    <div><span class="label">Flight</span><strong>{{ $booking->flightNumbers() }}</strong></div>
                    <div><span class="label">Cabin</span><strong>{{ $booking->cabin }}</strong></div>
                    <div><span class="label">Paid</span><strong>{{ \App\Support\Money::format($booking->total_cents) }}</strong></div>
                </div>

                <div class="tickets">
                    <span class="label">Passengers and ticket numbers</span>
                    @foreach ($booking->passengers as $passenger)
                        <div class="ticket-row">
                            <strong>{{ $passenger->fullName() }}</strong>
                            <span>Ticket <strong data-test="ticket-number">{{ $passenger->ticket_number ?? 'pending' }}</strong></span>
                        </div>
                    @endforeach
                </div>

                @if ($booking->isConfirmed())
                    <div style="display: flex; gap: 10px; flex-wrap: wrap">
                        <a class="btn btn--primary" href="{{ $ticketUrl }}" data-test="download-ticket">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12m0 0-4-4m4 4 4-4M5 21h14"/></svg>
                            Download e-ticket (PDF)
                        </a>
                        <a class="btn btn--ghost" href="{{ route('flights.index') }}">Book another flight</a>
                    </div>
                @elseif ($booking->failure_reason)
                    <div class="alert">{{ $booking->failure_reason }} The money was returned to the wallet.</div>
                @endif
            </div>
        </article>
    </div>
</x-layouts::site>
