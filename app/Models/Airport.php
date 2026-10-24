<?php

namespace App\Models;

use Database\Factories\AirportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Airports offered in the search boxes.
 *
 * @property int $id
 * @property string $iata_code
 * @property string $name
 * @property string $city
 * @property string $country
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['iata_code', 'name', 'city', 'country'])]
class Airport extends Model
{
    /** @use HasFactory<AirportFactory> */
    use HasFactory;

    public function label(): string
    {
        return "{$this->city} ({$this->iata_code})";
    }

    /**
     * Find the city name for an airport code, or the code itself.
     */
    public static function cityFor(string $code): string
    {
        return once(fn (): array => static::query()->pluck('city', 'iata_code')->all())[$code] ?? $code;
    }
}
