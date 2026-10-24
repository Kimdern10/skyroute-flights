<?php

namespace App\Services;

use App\Enums\WalletTransactionType;
use App\Models\Booking;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Moves money in and out of a customer's wallet and writes the statement line.
 */
class Wallet
{
    /**
     * Add (positive) or take (negative) cents, locking the customer's row so two changes
     * at the same moment cannot overwrite each other.
     */
    public function change(User $user, int $amountCents, WalletTransactionType $type, string $description, ?User $by = null, ?Booking $booking = null): WalletTransaction
    {
        return DB::transaction(function () use ($user, $amountCents, $type, $description, $by, $booking): WalletTransaction {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $transaction = $this->record($locked, $amountCents, $type, $description, $by, $booking);
            $user->balance_cents = $locked->balance_cents;

            return $transaction;
        });
    }

    /**
     * Change the balance of a user row that is already locked inside a transaction.
     */
    public function record(User $lockedUser, int $amountCents, WalletTransactionType $type, string $description, ?User $by = null, ?Booking $booking = null): WalletTransaction
    {
        if ($lockedUser->balance_cents + $amountCents < 0) {
            throw new InvalidArgumentException('The wallet balance cannot go below zero.');
        }

        $lockedUser->balance_cents += $amountCents;
        $lockedUser->save();

        return $lockedUser->walletTransactions()->create([
            'type' => $type,
            'amount_cents' => $amountCents,
            'balance_after_cents' => $lockedUser->balance_cents,
            'description' => $description,
            'booking_id' => $booking?->id,
            'created_by' => $by?->id,
        ]);
    }
}
