<?php

namespace App\Actions;

use App\Enums\TopUpStatus;
use App\Models\TopUpRequest;
use App\Services\Paystack;

/**
 * Check a card top-up with Paystack and add the money once Paystack says it was paid.
 * Called when the customer comes back from Paystack and again by Paystack's webhook;
 * whichever arrives first adds the money, the other does nothing.
 */
class CompleteCardTopUp
{
    public function __construct(private Paystack $paystack, private ReviewTopUp $review) {}

    public function handle(string $reference): ?TopUpRequest
    {
        $topUp = TopUpRequest::query()->where('method', 'card')->where('payment_reference', $reference)->first();

        if (! $topUp || $topUp->status !== TopUpStatus::Pending) {
            return $topUp;
        }

        // Never trust the browser or the webhook body alone: ask Paystack directly.
        $payment = $this->paystack->verify($reference);

        if ($payment['status'] === 'success' && $payment['currency'] === $topUp->charged_currency && $payment['amount'] >= (int) $topUp->charged_amount) {
            return $this->review->handle($topUp->id, TopUpStatus::Approved) ?? $topUp->fresh();
        }

        if ($payment['status'] === 'success') {
            return $this->review->handle($topUp->id, TopUpStatus::Rejected, note: "The amount paid did not match the top-up. Please contact support with reference {$reference} and we will sort it out.") ?? $topUp->fresh();
        }

        if ($payment['status'] === 'failed') {
            return $this->review->handle($topUp->id, TopUpStatus::Rejected, note: 'The card payment did not go through, so nothing was added. You can try again.') ?? $topUp->fresh();
        }

        return $topUp;
    }
}
