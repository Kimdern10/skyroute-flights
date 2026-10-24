<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>E-ticket {{ $booking->reference }}</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: DejaVu Sans, sans-serif; color: #0b1b3a; font-size: 11px; }
        .head { background: #0b3d91; color: #fff; padding: 16px 20px; border-radius: 10px 10px 0 0; }
        .brand { font-size: 20px; font-weight: bold; }
        .brand span { color: #ff8a3d; }
        .muted { color: #5a6b8c; }
        .label { font-size: 8.5px; text-transform: uppercase; letter-spacing: 1px; color: #5a6b8c; }
        .box { border: 1px solid #d7e0f0; border-top: 0; padding: 18px 20px; border-radius: 0 0 10px 10px; }
        .ref { font-size: 26px; font-weight: bold; letter-spacing: 4px; color: #0b3d91; }
        .route td { vertical-align: middle; }
        .code { font-size: 30px; font-weight: bold; }
        .plane { text-align: center; color: #ff7a1a; font-size: 16px; }
        table { border-collapse: collapse; width: 100%; }
        .list th { text-align: left; font-size: 8.5px; text-transform: uppercase; letter-spacing: 1px; color: #5a6b8c; border-bottom: 1px solid #d7e0f0; padding: 6px 4px; }
        .list td { padding: 7px 4px; border-bottom: 1px solid #eef2f9; }
        .section { margin-top: 18px; font-size: 12px; font-weight: bold; color: #0b3d91; }
        .stub { margin-top: 18px; border-top: 2px dashed #c4d0e6; padding-top: 12px; }
        .barcode { font-family: DejaVu Sans Mono, monospace; letter-spacing: 1px; font-size: 22px; }
    </style>
</head>
<body>
    <div class="head">
        <table>
            <tr>
                <td class="brand">{{ $site['business_name'] }} <span>&#9992;</span></td>
                <td style="text-align:right;">ELECTRONIC TICKET<br><span style="color:#c9d7f2;">Issued {{ $booking->updated_at?->format('j M Y') }}</span></td>
            </tr>
        </table>
    </div>
    <div class="box">
        <table>
            <tr>
                <td>
                    <div class="label">Booking reference</div>
                    <div class="ref">{{ $booking->reference }}</div>
                </td>
                <td>
                    <div class="label">Airline reference</div>
                    <div style="font-size:16px;font-weight:bold;">{{ $booking->airline_reference ?: '—' }}</div>
                </td>
                <td style="text-align:right;">
                    <div class="label">Status</div>
                    <div style="font-size:14px;font-weight:bold;color:#13804b;">{{ strtoupper($booking->status->label()) }}</div>
                </td>
            </tr>
        </table>

        <table class="route" style="margin-top:16px;background:#f4f7fd;">
            <tr>
                <td style="padding:14px;">
                    <div class="code">{{ $booking->origin }}</div>
                    <div>{{ \App\Models\Airport::cityFor($booking->origin) }}</div>
                    <div class="muted">{{ $booking->departs_at->format('D j M Y') }} · {{ $booking->departs_at->format('H:i') }}</div>
                </td>
                <td class="plane">- - - &#9992; - - -<br><span class="muted" style="font-size:9px;">{{ $booking->stops() === 0 ? 'Direct' : $booking->stops().' '.str('stop')->plural($booking->stops()) }}</span></td>
                <td style="padding:14px;text-align:right;">
                    <div class="code">{{ $booking->destination }}</div>
                    <div>{{ \App\Models\Airport::cityFor($booking->destination) }}</div>
                    <div class="muted">{{ $booking->arrives_at->format('D j M Y') }} · {{ $booking->arrives_at->format('H:i') }}</div>
                </td>
            </tr>
        </table>

        <div class="section">Flights</div>
        <table class="list">
            <tr><th>Flight</th><th>From</th><th>To</th><th>Departs</th><th>Arrives</th><th>Aircraft</th></tr>
            @foreach ($booking->segments as $segment)
                <tr>
                    <td><strong>{{ $segment['carrier_code'] }} {{ $segment['flight_number'] }}</strong><br><span class="muted">{{ $segment['carrier_name'] }}</span></td>
                    <td>{{ $segment['origin'] }}<br><span class="muted">{{ $segment['origin_name'] }}</span></td>
                    <td>{{ $segment['destination'] }}<br><span class="muted">{{ $segment['destination_name'] }}</span></td>
                    <td>{{ \Illuminate\Support\Carbon::parse($segment['departs_at'])->format('j M, H:i') }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($segment['arrives_at'])->format('j M, H:i') }}</td>
                    <td>{{ $segment['aircraft'] ?: '—' }}</td>
                </tr>
            @endforeach
        </table>

        <div class="section">Passengers</div>
        <table class="list">
            <tr><th>Name</th><th>Date of birth</th><th>Cabin</th><th>Ticket number</th></tr>
            @foreach ($booking->passengers as $passenger)
                <tr>
                    <td><strong>{{ strtoupper($passenger->family_name) }}/{{ strtoupper($passenger->given_name) }} {{ strtoupper($passenger->title) }}</strong></td>
                    <td>{{ $passenger->born_on->format('j M Y') }}</td>
                    <td>{{ $booking->cabin }}</td>
                    <td><strong>{{ $passenger->ticket_number }}</strong></td>
                </tr>
            @endforeach
        </table>

        <table style="margin-top:16px;">
            <tr>
                <td>
                    <div class="label">Paid from wallet</div>
                    <div style="font-size:16px;font-weight:bold;">{{ \App\Support\Money::format($booking->total_cents) }}</div>
                </td>
                <td style="text-align:right;" class="muted">
                    Contact: {{ $booking->contact_email }} · {{ $booking->contact_phone }}
                </td>
            </tr>
        </table>

        <div class="stub">
            <table>
                <tr>
                    <td class="barcode">{{ $booking->reference }}{{ $booking->passengers->first()?->ticket_number }}</td>
                    <td style="text-align:right;" class="muted">
                        Check in online or at the airport at least 3 hours before international flights.<br>
                        Bring this e-ticket and a valid passport. Help: {{ $site['support_email'] }} · {{ $site['support_phone'] }}
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
