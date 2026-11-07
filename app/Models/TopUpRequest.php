<?php

namespace App\Models;

use App\Enums\TopUpStatus;
use Database\Factories\TopUpRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Money going into a customer's wallet: either a bank transfer the admin checks and approves,
 * or a card payment through Paystack that is approved automatically once Paystack confirms it.
 *
 * @property int $id
 * @property int $user_id
 * @property string $method bank (customer sent a transfer) or card (paid with Paystack)
 * @property int $amount_cents
 * @property int|null $charged_amount Card payments: what Paystack charged, in the currency's smallest unit (e.g. kobo).
 * @property string|null $charged_currency
 * @property string $payment_reference
 * @property string|null $note
 * @property TopUpStatus $status
 * @property string|null $admin_note
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['method', 'amount_cents', 'charged_amount', 'charged_currency', 'payment_reference', 'note', 'status', 'admin_note', 'reviewed_by', 'reviewed_at'])]
class TopUpRequest extends Model
{
    /** @use HasFactory<TopUpRequestFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'method' => 'bank',
    ];

    protected function casts(): array
    {
        return [
            'status' => TopUpStatus::class,
            'amount_cents' => 'integer',
            'charged_amount' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public function isCard(): bool
    {
        return $this->method === 'card';
    }

    /**
     * @param  Builder<TopUpRequest>  $query
     */
    public function scopeBankTransfers(Builder $query): void
    {
        $query->where('method', 'bank');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
