<x-mail::message>
# You're all set, {{ $booking->passengers->first()?->given_name }}!

Your ticket is confirmed. Your e-ticket is attached as a PDF. Bring it, with your passport or ID, to the airport.

<x-mail::panel>
**Booking reference: {{ $booking->reference }}**<br>
Airline reference: {{ $booking->airline_reference ?: 'shown on your e-ticket' }}

**{{ \App\Models\Airport::cityFor($booking->origin) }} ({{ $booking->origin }}) → {{ \App\Models\Airport::cityFor($booking->destination) }} ({{ $booking->destination }})**<br>
{{ $booking->departs_at->format('D j M Y, H:i') }} · {{ $booking->carrier_name }} {{ $booking->flightNumbers() }}
</x-mail::panel>

<x-mail::table>
| Passenger | Ticket number |
|:--|:--|
@foreach ($booking->passengers as $passenger)
| {{ $passenger->fullName() }} | {{ $passenger->ticket_number }} |
@endforeach
</x-mail::table>

Paid from your wallet: **{{ \App\Support\Money::format($booking->total_cents) }}**

<x-mail::button :url="route('bookings.show', $booking)">
View my booking
</x-mail::button>

Safe travels,<br>
The {{ $site['business_name'] }} team
</x-mail::message>
