<!DOCTYPE html>
<html lang="en">
<body style="margin:0;padding:24px;background:#eef3fb;font-family:Segoe UI,Arial,sans-serif;color:#0b1b3a;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:14px;overflow:hidden;">
        <tr>
            <td style="background:#0b3d91;padding:22px 28px;color:#ffffff;font-size:20px;font-weight:700;">
                {{ $site['business_name'] }} <span style="color:#ff8a3d;">&#9992;</span>
            </td>
        </tr>
        <tr>
            <td style="padding:28px;">
                <p style="margin:0 0 12px;font-size:16px;">Hello {{ $booking->passengers->first()?->given_name }},</p>
                <p style="margin:0 0 20px;line-height:1.5;">Your ticket is confirmed. Your e-ticket is attached as a PDF. Please bring it, with your passport or ID, to the airport.</p>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7fd;border-radius:10px;">
                    <tr>
                        <td style="padding:16px 18px;">
                            <div style="font-size:12px;color:#5a6b8c;text-transform:uppercase;letter-spacing:.06em;">Booking reference</div>
                            <div style="font-size:26px;font-weight:800;letter-spacing:.12em;color:#0b3d91;">{{ $booking->reference }}</div>
                            @if ($booking->airline_reference)
                                <div style="font-size:13px;color:#5a6b8c;">Airline reference: {{ $booking->airline_reference }}</div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 18px 16px;font-size:15px;line-height:1.6;">
                            <strong>{{ \App\Models\Airport::cityFor($booking->origin) }} ({{ $booking->origin }}) &rarr; {{ \App\Models\Airport::cityFor($booking->destination) }} ({{ $booking->destination }})</strong><br>
                            {{ $booking->departs_at->format('D j M Y, H:i') }} &middot; {{ $booking->carrier_name }} {{ $booking->flightNumbers() }}
                        </td>
                    </tr>
                </table>

                <p style="margin:20px 0 6px;font-weight:700;">Passengers</p>
                @foreach ($booking->passengers as $passenger)
                    <p style="margin:0 0 4px;">{{ $passenger->fullName() }} &middot; Ticket {{ $passenger->ticket_number }}</p>
                @endforeach

                <p style="margin:24px 0 0;">
                    <a href="{{ route('bookings.show', $booking) }}" style="display:inline-block;background:#ff7a1a;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:999px;font-weight:700;">View my booking</a>
                </p>
                <p style="margin:24px 0 0;font-size:13px;color:#5a6b8c;">Questions? Email {{ $site['support_email'] }} or call {{ $site['support_phone'] }}.</p>
            </td>
        </tr>
    </table>
</body>
</html>
