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
        'business_address' => '',
        'paystack_secret' => '',
        'paystack_currency' => 'NGN',
        'paystack_rate' => '1550',
        'privacy_policy' => '',
        'terms' => '',
    ];

    /**
     * Saved values that are stored encrypted.
     *
     * @var list<string>
     */
    public const array ENCRYPTED = ['duffel_token', 'paystack_secret'];

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
     * The privacy policy (Markdown): the owner's own, or a starter version with the business details filled in.
     */
    public static function privacyPolicy(): string
    {
        $saved = trim(self::get('privacy_policy'));

        if ($saved !== '') {
            return $saved;
        }

        $business = self::get('business_name');
        $email = self::get('support_email');

        return <<<MARKDOWN
            This page explains what personal information {$business} collects, why, and what you can ask us to do with it.

            ## What we collect

            - **Your account:** name, email address, phone number and password (stored scrambled, so nobody can read it).
            - **Passengers:** the names, titles, genders and dates of birth you type in when you book. Airlines need these to issue tickets.
            - **Payments:** your wallet top-ups and bookings. Card payments are handled by Paystack on its own secure page; we never see or store your card number.
            - **Technical details:** your IP address and the pages you visit, to keep the site secure and stop abuse.

            ## Why we use it

            - To book your flights with the airline and send you your e-ticket.
            - To run your wallet and show you your statement.
            - To send you important emails about your account and bookings, such as codes, tickets and top-up updates.
            - To protect the site against fraud.

            We do **not** sell your information.

            ## Who we share it with

            - **Airlines and our flight booking provider (Duffel)**, who need passenger details to issue tickets.
            - **Paystack**, to take card payments.
            - **Our hosting and email providers**, who store and send data for us.

            ## How long we keep it

            We keep bookings and payment records for as long as the law requires for accounting, usually up to 6 years. You can ask us to delete your account at any time; we keep only what we are legally required to.

            ## Your rights

            You can ask to see, correct or delete your information. Email {$email}.

            ## Changes

            We may update this page. The latest version is always shown here.
            MARKDOWN;
    }

    /**
     * The terms of use (Markdown): the owner's own, or a starter version.
     */
    public static function terms(): string
    {
        $saved = trim(self::get('terms'));

        if ($saved !== '') {
            return $saved;
        }

        $business = self::get('business_name');
        $email = self::get('support_email');

        return <<<MARKDOWN
            By creating an account or booking with {$business}, you agree to these terms.

            ## Your account

            Keep your password private. You are responsible for bookings made from your account. You must be at least 18 to create an account.

            ## Your wallet

            - Tickets are paid from your {$business} wallet, in US dollars.
            - Bank transfer top-ups are added once we have confirmed the money arrived. Card top-ups are added as soon as Paystack confirms the payment.
            - Card payments may be charged in naira at the exchange rate shown before you pay.
            - To withdraw unused wallet money, email {$email}. Bank or card fees may be deducted.

            ## Booking flights

            - Fares and seats come from the airlines and can change until your ticket is issued.
            - Your booking is confirmed only when you see a booking reference and ticket number.
            - Passenger names must match their passports exactly. Name changes may not be possible, or may cost extra.
            - If the airline cannot issue the ticket, the money goes straight back to your wallet.

            ## Changes, cancellations and refunds

            Each airline fare has its own rules. Some tickets cannot be changed or refunded. Email {$email} with your booking reference and we will tell you what the airline allows and any fees.

            ## Travel documents

            You are responsible for having a valid passport, visas and health documents for your trip, and for arriving at the airport on time.

            ## Our responsibility

            We arrange your booking with the airline; the airline is responsible for the flight itself, including delays, cancellations and baggage.

            ## Contact

            Questions about these terms? Email {$email}.
            MARKDOWN;
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
