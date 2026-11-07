<?php

namespace App\Http\Controllers;

use App\Actions\CompleteCardTopUp;
use App\Enums\TopUpStatus;
use App\Services\Paystack;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;

class PaystackController extends Controller
{
    /**
     * The customer comes back here from Paystack's payment page.
     */
    public function callback(Request $request, CompleteCardTopUp $complete): RedirectResponse
    {
        $reference = (string) $request->query('reference', $request->query('trxref', ''));

        try {
            $topUp = $complete->handle($reference);
        } catch (RuntimeException $exception) {
            return to_route('wallet')->with('wallet-error', $exception->getMessage());
        }

        if (! $topUp || $topUp->user_id !== $request->user()->id) {
            return to_route('wallet')->with('wallet-error', 'We could not find that card payment.');
        }

        return match ($topUp->status) {
            TopUpStatus::Approved => to_route('wallet')->with('wallet-success', Money::format($topUp->amount_cents).' was added to your wallet.'),
            TopUpStatus::Rejected => to_route('wallet')->with('wallet-error', (string) $topUp->admin_note),
            TopUpStatus::Pending => to_route('wallet')->with('wallet-error', 'Your card payment is not finished yet. If you paid, it will show here within a few minutes.'),
        };
    }

    /**
     * Paystack tells us about payments here too, in case the customer closes the page before coming back.
     */
    public function webhook(Request $request, Paystack $paystack, CompleteCardTopUp $complete): Response
    {
        abort_unless($paystack->hasValidSignature($request->getContent(), $request->header('x-paystack-signature')), Response::HTTP_UNAUTHORIZED);

        if ($request->input('event') === 'charge.success' && filled($request->input('data.reference'))) {
            $complete->handle((string) $request->input('data.reference'));
        }

        return response()->noContent(Response::HTTP_OK);
    }
}
