<?php

namespace App\Models;

use App\Enums\TopUpStatus;
use Database\Factories\TopUpRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A customer saying "I sent you money, please add it to my wallet". The admin checks and approves it.
 *
 * @property int $id
 * @property int $user_id
 * @property int $amount_cents
 * @property string $payment_reference
 * @property string|null $note
 * @property TopUpStatus $status
 * @property string|null $admin_note
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['amount_cents', 'payment_reference', 'note', 'status', 'admin_note', 'reviewed_by', 'reviewed_at'])]
class TopUpRequest extends Model
{
    /** @use HasFactory<TopUpRequestFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => TopUpStatus::class,
            'amount_cents' => 'integer',
            'reviewed_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
