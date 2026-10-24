<?php

namespace App\Models;

use Database\Factories\FlightFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * One flight we sell seats on. The airline API finds it; seats are counted here so a
 * flight can be shown as fully booked and two people can never take the last seat.
 *
 * @property int $id
 * @property string $carrier_code
 * @property string $carrier_name
 * @property string $flight_number
 * @property string $origin
 * @property string $destination
 * @property Carbon $departs_at
 * @property Carbon $arrives_at
 * @property int|null $seats_total
 * @property int $seats_booked
 * @property int|null $price_override_cents
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['carrier_code', 'carrier_name', 'flight_number', 'origin', 'destination', 'departs_at', 'arrives_at', 'seats_total', 'seats_booked', 'price_override_cents'])]
class Flight extends Model
{
    /** @use HasFactory<FlightFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'departs_at' => 'datetime',
            'arrives_at' => 'datetime',
            'seats_total' => 'integer',
            'seats_booked' => 'integer',
            'price_override_cents' => 'integer',
        ];
    }

    /**
     * @return BelongsToMany<Booking, $this>
     */
    public function bookings(): BelongsToMany
    {
        return $this->belongsToMany(Booking::class)->withPivot('seats');
    }

    /**
     * Seats we sell on this flight: its own number, or the default from the settings.
     */
    public function capacity(): int
    {
        return $this->seats_total ?? Setting::int('seats_per_flight');
    }

    public function seatsLeft(): int
    {
        return max(0, $this->capacity() - $this->seats_booked);
    }

    public function isFull(): bool
    {
        return $this->seatsLeft() === 0;
    }

    public function code(): string
    {
        return $this->carrier_code.' '.$this->flight_number;
    }
}
