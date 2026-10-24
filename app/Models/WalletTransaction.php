<?php

namespace App\Models;

use App\Enums\WalletTransactionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One line of a customer's wallet statement.
 *
 * @property int $id
 * @property int $user_id
 * @property WalletTransactionType $type
 * @property int $amount_cents
 * @property int $balance_after_cents
 * @property string $description
 * @property int|null $booking_id
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['type', 'amount_cents', 'balance_after_cents', 'description', 'booking_id', 'created_by'])]
class WalletTransaction extends Model
{
    protected function casts(): array
    {
        return [
            'type' => WalletTransactionType::class,
            'amount_cents' => 'integer',
            'balance_after_cents' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
