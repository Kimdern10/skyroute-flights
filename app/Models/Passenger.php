<?php

namespace App\Models;

use Database\Factories\PassengerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $booking_id
 * @property string|null $provider_passenger_id
 * @property string $title
 * @property string $given_name
 * @property string $family_name
 * @property string $gender
 * @property Carbon $born_on
 * @property string|null $ticket_number
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['provider_passenger_id', 'title', 'given_name', 'family_name', 'gender', 'born_on', 'ticket_number'])]
class Passenger extends Model
{
    /** @use HasFactory<PassengerFactory> */
    use HasFactory;

    /**
     * Titles the airline accepts.
     *
     * @var array<string, string>
     */
    public const array TITLES = ['mr' => 'Mr', 'ms' => 'Ms', 'mrs' => 'Mrs', 'miss' => 'Miss', 'dr' => 'Dr'];

    protected function casts(): array
    {
        return ['born_on' => 'date'];
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function fullName(): string
    {
        return trim((self::TITLES[$this->title] ?? '').' '.$this->given_name.' '.$this->family_name);
    }
}
