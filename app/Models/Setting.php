<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Once;

/**
 * Key/value store for business details, pricing and the flight API key.
 *
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    /**
     * Values used until the owner saves their own.
     *
     * @var array<string, string>
     */
    public const array DEFAULTS = [
        'business_name' => 'SkyRoute',
        'tagline' => 'Book flights worldwide in minutes and pay from your SkyRoute wallet.',
        'support_email' => 'support@skyroute.example',
        'support_phone' => '+1 (555) 010-2030',
        'markup_percent' => '5',
        'markup_fixed_cents' => '1500',
        'seats_per_flight' => '20',
        'currency_rates' => "GBP=1.27\nEUR=1.08\nCAD=0.72\nAUD=0.66",
        'top_up_instructions' => "Send the amount by bank transfer to:\nBank: First Example Bank\nAccount name: SkyRoute Travel LLC\nAccount number: 0000000000\nUse your email address as the payment reference, then fill in the form.",
        'duffel_token' => '',
    ];

    /**
     * Saved values that are stored encrypted.
     *
     * @var list<string>
     */
    public const array ENCRYPTED = ['duffel_token'];

    private const string CACHE_KEY = 'site-settings';

    /**
     * Get every stored value, falling back to the defaults.
     *
     * @return array<string, string>
     */
    public static function values(): array
    {
        // Read once per request; defaults go on top so new keys are never missing from an old cache.
        return once(fn (): array => array_merge(self::DEFAULTS, Cache::rememberForever(
            self::CACHE_KEY,
            fn (): array => static::query()->pluck('value', 'key')->map(fn (?string $value): string => (string) $value)->all(),
        )));
    }

    /**
     * Get one value, decrypted when it is a secret.
     */
    public static function get(string $key): string
    {
        $value = self::values()[$key] ?? '';

        if ($value !== '' && in_array($key, self::ENCRYPTED, true)) {
            try {
                return Crypt::decryptString($value);
            } catch (\Throwable) {
                return '';
            }
        }

        return $value;
    }

    public static function int(string $key): int
    {
        return (int) self::get($key);
    }

    /**
     * Exchange rates to US dollars, e.g. ['GBP' => 1.27].
     *
     * @return array<string, float>
     */
    public static function currencyRates(): array
    {
        $rates = ['USD' => 1.0];

        foreach (preg_split('/\R/', self::get('currency_rates')) ?: [] as $line) {
            if (preg_match('/^\s*([A-Za-z]{3})\s*=\s*([0-9.]+)\s*$/', $line, $match) && (float) $match[2] > 0) {
                $rates[strtoupper($match[1])] = (float) $match[2];
            }
        }

        return $rates;
    }

    /**
     * Save several values at once.
     *
     * @param  array<string, string|int|null>  $values
     */
    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            $value = (string) $value;

            if ($value !== '' && in_array($key, self::ENCRYPTED, true)) {
                $value = Crypt::encryptString($value);
            }

            static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CACHE_KEY);
        Once::flush();
    }
}
