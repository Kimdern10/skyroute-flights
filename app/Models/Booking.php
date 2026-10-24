<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A customer's ticket purchase for one flight (with any connections).
 *
 * @property int $id
 * @property int $user_id
 * @property string $reference
 * @property BookingStatus $status
 * @property string $offer_id
 * @property string|null $provider_order_id
 * @property string|null $airline_reference
 * @property string $carrier_code
 * @property string $carrier_name
 * @property string $origin
 * @property string $destination
 * @property Carbon $departs_at
 * @property Carbon $arrives_at
 * @property string $cabin
 * @property int $passengers_count
 * @property list<array{carrier_code: string, carrier_name: string, flight_number: string, origin: string, origin_name: string, destination: string, destination_name: string, departs_at: string, arrives_at: string, aircraft: string|null}> $segments
 * @property int $fare_cents
 * @property int $markup_cents
 * @property int $total_cents
 * @property string $provider_amount
 * @property string $provider_currency
 * @property string $contact_email
 * @property string $contact_phone
 * @property string|null $failure_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'reference', 'status', 'offer_id', 'provider_order_id', 'airline_reference', 'carrier_code', 'carrier_name',
    'origin', 'destination', 'departs_at', 'arrives_at', 'cabin', 'passengers_count', 'segments',
    'fare_cents', 'markup_cents', 'total_cents', 'provider_amount', 'provider_currency',
    'contact_email', 'contact_phone', 'failure_reason',
])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'departs_at' => 'datetime',
            'arrives_at' => 'datetime',
            'segments' => 'array',
            'passengers_count' => 'integer',
            'fare_cents' => 'integer',
            'markup_cents' => 'integer',
            'total_cents' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Passenger, $this>
     */
    public function passengers(): HasMany
    {
        return $this->hasMany(Passenger::class);
    }

    /**
     * @return BelongsToMany<Flight, $this>
     */
    public function flights(): BelongsToMany
    {
        return $this->belongsToMany(Flight::class)->withPivot('seats');
    }

    /**
     * @param  Builder<Booking>  $query
     */
    public function scopeConfirmed(Builder $query): void
    {
        $query->where('status', BookingStatus::Confirmed);
    }

    public function isConfirmed(): bool
    {
        return $this->status === BookingStatus::Confirmed;
    }

    public function stops(): int
    {
        return max(0, count($this->segments) - 1);
    }

    public function flightNumbers(): string
    {
        return collect($this->segments)->map(fn (array $segment): string => $segment['carrier_code'].' '.$segment['flight_number'])->implode(', ');
    }

    /**
     * A new six-character reference without look-alike letters (no 0/O, 1/I).
     */
    public static function newReference(): string
    {
        do {
            $reference = collect(range(1, 6))->map(fn (): string => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'[random_int(0, 31)])->implode('');
        } while (static::query()->where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * Our own ticket number, used when the airline does not send one.
     */
    public static function newTicketNumber(): string
    {
        return '900'.Str::padLeft((string) random_int(0, 9_999_999_999), 10, '0');
    }
}
