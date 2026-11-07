<?php

namespace App\Actions;

use App\Enums\TopUpStatus;
use App\Enums\WalletTransactionType;
use App\Models\TopUpRequest;
use App\Models\User;
use App\Notifications\TopUpReviewed;
use App\Services\Wallet;
use Illuminate\Support\Facades\DB;

/**
 * Approve a top-up (money goes into the wallet) or reject it, then email the customer.
 */
class ReviewTopUp
{
    public function __construct(private Wallet $wallet) {}

    /**
     * Returns null when the top-up was already approved or rejected, so a double click
     * (or Paystack's callback and webhook arriving together) never adds the money twice.
     */
    public function handle(int $topUpId, TopUpStatus $decision, ?User $admin = null, ?string $note = null): ?TopUpRequest
    {
        $topUp = DB::transaction(function () use ($topUpId, $decision, $admin, $note): ?TopUpRequest {
            $topUp = TopUpRequest::query()->whereKey($topUpId)->lockForUpdate()->firstOrFail();

            if ($topUp->status !== TopUpStatus::Pending) {
                return null;
            }

            if ($decision === TopUpStatus::Approved) {
                $user = User::query()->whereKey($topUp->user_id)->lockForUpdate()->firstOrFail();
                $label = $topUp->isCard() ? 'Card top-up' : 'Top-up';
                $this->wallet->record($user, $topUp->amount_cents, WalletTransactionType::TopUp, "{$label} {$topUp->payment_reference}", by: $admin);
            }

            $topUp->update([
                'status' => $decision,
                'admin_note' => filled($note) ? $note : null,
                'reviewed_by' => $admin?->id,
                'reviewed_at' => now(),
            ]);

            return $topUp;
        });

        if ($topUp) {
            try {
                $topUp->user->refresh()->notify(new TopUpReviewed($topUp));
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return $topUp;
    }
}
