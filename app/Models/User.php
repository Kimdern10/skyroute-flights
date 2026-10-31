<?php

namespace App\Models;

use App\Notifications\VerifyEmailCode;
use App\Support\Money;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string|null $email_verification_code
 * @property Carbon|null $email_verification_code_expires_at
 * @property string|null $phone
 * @property string $password
 * @property bool $is_admin
 * @property int $balance_cents
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token', 'email_verification_code'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * How long an emailed verification code works.
     */
    public const int VERIFICATION_CODE_MINUTES = 15;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_admin' => false,
        'balance_cents' => 0,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_verification_code_expires_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'balance_cents' => 'integer',
        ];
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * @return HasMany<WalletTransaction, $this>
     */
    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * @return HasMany<TopUpRequest, $this>
     */
    public function topUpRequests(): HasMany
    {
        return $this->hasMany(TopUpRequest::class);
    }

    public function balance(): string
    {
        return Money::format($this->balance_cents);
    }

    /**
     * Email a new 6-digit code instead of a link. Only a hash of the code is saved.
     */
    public function sendEmailVerificationNotification(): void
    {
        $code = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);

        $this->forceFill([
            'email_verification_code' => Hash::make($code),
            'email_verification_code_expires_at' => now()->addMinutes(self::VERIFICATION_CODE_MINUTES),
        ])->save();

        $this->notify(new VerifyEmailCode($code, self::VERIFICATION_CODE_MINUTES));
    }

    /**
     * Confirm the email address if the code is right and has not expired.
     */
    public function verifyEmailWithCode(string $code): bool
    {
        if (
            $this->email_verification_code === null
            || $this->email_verification_code_expires_at?->isPast() !== false
            || ! Hash::check(preg_replace('/\D/', '', $code), $this->email_verification_code)
        ) {
            return false;
        }

        $this->forceFill([
            'email_verified_at' => now(),
            'email_verification_code' => null,
            'email_verification_code_expires_at' => null,
        ])->save();

        return true;
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }
}
