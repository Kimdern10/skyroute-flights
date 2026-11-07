<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Card payments for wallet top-ups (https://paystack.com/docs/api). The customer is sent
 * to Paystack's own payment page, so card details never touch this site.
 */
class Paystack
{
    public function isConfigured(): bool
    {
        return $this->secret() !== '';
    }

    public function currency(): string
    {
        return strtoupper(Setting::get('paystack_currency')) ?: 'NGN';
    }

    /**
     * What to charge for a top-up of this many US cents, in the card currency's smallest unit (kobo for NGN).
     */
    public function chargeFor(int $amountCents): int
    {
        $rate = $this->currency() === 'USD' ? 1.0 : (float) Setting::get('paystack_rate');

        return (int) round($amountCents * $rate);
    }

    /**
     * Start a payment and return the Paystack page the customer pays on.
     *
     * @param  array<string, mixed>  $metadata
     *
     * @throws RuntimeException
     */
    public function initialize(string $email, int $amount, string $reference, string $callbackUrl, array $metadata = []): string
    {
        $response = $this->send(fn (PendingRequest $http) => $http->post('/transaction/initialize', [
            'email' => $email,
            'amount' => $amount,
            'currency' => $this->currency(),
            'reference' => $reference,
            'callback_url' => $callbackUrl,
            'metadata' => $metadata,
        ]));

        return (string) $response['data']['authorization_url'];
    }

    /**
     * Ask Paystack what happened to a payment.
     *
     * @return array{status: string, amount: int, currency: string}
     *
     * @throws RuntimeException
     */
    public function verify(string $reference): array
    {
        $response = $this->send(fn (PendingRequest $http) => $http->get('/transaction/verify/'.rawurlencode($reference)));

        return [
            'status' => (string) ($response['data']['status'] ?? ''),
            'amount' => (int) ($response['data']['amount'] ?? 0),
            'currency' => strtoupper((string) ($response['data']['currency'] ?? '')),
        ];
    }

    /**
     * Webhooks are signed with the secret key, so a fake "payment succeeded" message is ignored.
     */
    public function hasValidSignature(string $body, ?string $signature): bool
    {
        return $this->isConfigured()
            && $signature !== null
            && hash_equals(hash_hmac('sha512', $body, $this->secret()), $signature);
    }

    /**
     * The key saved in the admin, or PAYSTACK_SECRET_KEY from .env.
     */
    private function secret(): string
    {
        return Setting::get('paystack_secret') ?: (string) config('services.paystack.secret');
    }

    /**
     * @param  callable(PendingRequest): Response  $request
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
    private function send(callable $request): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Card payments are not set up yet. Please top up by bank transfer.');
        }

        try {
            $response = $request(Http::baseUrl((string) config('services.paystack.url'))->withToken($this->secret())->acceptJson()->asJson()->timeout(30));
        } catch (ConnectionException $exception) {
            Log::warning('Paystack could not be reached', ['error' => $exception->getMessage()]);

            throw new RuntimeException('We could not reach the payment service. Please try again in a minute.');
        }

        if (! $response->successful() || $response->json('status') !== true) {
            Log::warning('Paystack request failed', ['status' => $response->status(), 'message' => $response->json('message')]);

            throw new RuntimeException('The payment service could not process this request. Please try again.');
        }

        return (array) $response->json();
    }
}
