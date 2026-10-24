<?php

namespace App\Services\Flights;

use App\Models\Setting;
use DateInterval;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Real flights from the Duffel API (https://duffel.com/docs). Works with a test token
 * (duffel_test_…) for trying it out, and a live token for real tickets.
 */
class DuffelFlightProvider implements FlightProvider
{
    public function isConfigured(): bool
    {
        return $this->token() !== '';
    }

    public function search(FlightSearch $search): Collection
    {
        $response = $this->send(fn (PendingRequest $http): Response => $http
            ->timeout(45)
            ->post('/air/offer_requests?return_offers=true&supplier_timeout=20000', [
                'data' => [
                    'slices' => [[
                        'origin' => $search->origin,
                        'destination' => $search->destination,
                        'departure_date' => $search->date,
                    ]],
                    'passengers' => array_fill(0, $search->passengers, ['type' => 'adult']),
                    'cabin_class' => $search->cabin,
                ],
            ]));

        return collect($response->json('data.offers', []))
            ->map(fn (array $offer): ?FlightOffer => $this->toOffer($offer))
            ->filter()
            ->sortBy(fn (FlightOffer $offer): float => (float) $offer->amount)
            ->unique(fn (FlightOffer $offer): string => $offer->journeyKey())
            ->values();
    }

    public function find(string $offerId): ?FlightOffer
    {
        $response = $this->send(fn (PendingRequest $http): Response => $http->get('/air/offers/'.rawurlencode($offerId)), allowNotFound: true);

        if ($response->notFound()) {
            return null;
        }

        return $this->toOffer($response->json('data', []));
    }

    public function book(FlightOffer $offer, array $passengers, string $email, string $phone): ProviderOrder
    {
        $response = $this->send(fn (PendingRequest $http): Response => $http
            ->timeout(60)
            ->post('/air/orders', [
                'data' => [
                    'type' => 'instant',
                    'selected_offers' => [$offer->id],
                    'payments' => [['type' => 'balance', 'currency' => $offer->currency, 'amount' => $offer->amount]],
                    'passengers' => array_map(fn (array $passenger): array => [
                        'id' => $passenger['provider_passenger_id'],
                        'title' => $passenger['title'],
                        'given_name' => $passenger['given_name'],
                        'family_name' => $passenger['family_name'],
                        'gender' => $passenger['gender'],
                        'born_on' => $passenger['born_on'],
                        'email' => $email,
                        'phone_number' => $phone,
                    ], $passengers),
                ],
            ]));

        $tickets = [];

        foreach ($response->json('data.documents', []) as $document) {
            if (($document['type'] ?? null) === 'electronic_ticket') {
                foreach ($document['passenger_ids'] ?? [] as $passengerId) {
                    $tickets[$passengerId] = (string) $document['unique_identifier'];
                }
            }
        }

        return new ProviderOrder(
            id: (string) $response->json('data.id'),
            bookingReference: $response->json('data.booking_reference'),
            ticketNumbers: $tickets,
        );
    }

    /**
     * The key saved in the admin, or DUFFEL_ACCESS_TOKEN from .env.
     */
    private function token(): string
    {
        return Setting::get('duffel_token') ?: (string) config('services.duffel.token');
    }

    /**
     * @param  callable(PendingRequest): Response  $request
     *
     * @throws FlightProviderException
     */
    private function send(callable $request, bool $allowNotFound = false): Response
    {
        if (! $this->isConfigured()) {
            throw FlightProviderException::notConfigured();
        }

        $http = Http::baseUrl((string) config('services.duffel.url'))
            ->withToken($this->token())
            ->withHeaders(['Duffel-Version' => 'v2'])
            ->acceptJson()
            ->asJson()
            ->timeout(30);

        try {
            $response = $request($http);
        } catch (ConnectionException $exception) {
            Log::warning('Duffel could not be reached', ['error' => $exception->getMessage()]);

            throw new FlightProviderException('We could not reach the airline right now. Please try again in a minute.');
        }

        if ($response->successful() || ($allowNotFound && $response->notFound())) {
            return $response;
        }

        $error = $response->json('errors.0', []);
        Log::warning('Duffel request failed', ['status' => $response->status(), 'error' => $error]);

        throw new FlightProviderException(match (true) {
            $response->status() === 401 => 'The flight API key is not valid. The site owner needs to check it in the admin.',
            $response->status() === 429 => 'Too many searches right now. Please wait a moment and try again.',
            ($error['code'] ?? '') === 'offer_no_longer_available' => 'This fare is no longer available. Please search again.',
            default => (string) ($error['message'] ?? 'The airline could not complete this request. Please try again.'),
        });
    }

    /**
     * @param  array<string, mixed>  $offer
     */
    private function toOffer(array $offer): ?FlightOffer
    {
        $slice = $offer['slices'][0] ?? null;

        if (! $slice || empty($slice['segments']) || empty($offer['id'])) {
            return null;
        }

        $segments = array_map(fn (array $segment): FlightSegment => new FlightSegment(
            carrierCode: (string) ($segment['marketing_carrier']['iata_code'] ?? $offer['owner']['iata_code'] ?? ''),
            carrierName: (string) ($segment['marketing_carrier']['name'] ?? $offer['owner']['name'] ?? ''),
            flightNumber: (string) ($segment['marketing_carrier_flight_number'] ?? ''),
            origin: (string) $segment['origin']['iata_code'],
            originName: (string) ($segment['origin']['city_name'] ?? $segment['origin']['name'] ?? $segment['origin']['iata_code']),
            destination: (string) $segment['destination']['iata_code'],
            destinationName: (string) ($segment['destination']['city_name'] ?? $segment['destination']['name'] ?? $segment['destination']['iata_code']),
            departsAt: CarbonImmutable::parse($segment['departing_at']),
            arrivesAt: CarbonImmutable::parse($segment['arriving_at']),
            aircraft: $segment['aircraft']['name'] ?? null,
        ), $slice['segments']);

        return new FlightOffer(
            id: (string) $offer['id'],
            amount: (string) $offer['total_amount'],
            currency: strtoupper((string) $offer['total_currency']),
            airlineName: (string) ($offer['owner']['name'] ?? $segments[0]->carrierName),
            airlineCode: (string) ($offer['owner']['iata_code'] ?? $segments[0]->carrierCode),
            airlineLogo: $offer['owner']['logo_symbol_url'] ?? null,
            segments: $segments,
            passengerIds: array_values(array_map(fn (array $passenger): string => (string) $passenger['id'], $offer['passengers'] ?? [])),
            cabin: (string) ($slice['segments'][0]['passengers'][0]['cabin_class_marketing_name'] ?? 'Economy'),
            durationMinutes: $this->minutes($slice['duration'] ?? null, $segments),
            expiresAt: isset($offer['expires_at']) ? CarbonImmutable::parse($offer['expires_at']) : null,
        );
    }

    /**
     * @param  list<FlightSegment>  $segments
     */
    private function minutes(?string $isoDuration, array $segments): int
    {
        try {
            $interval = new DateInterval((string) $isoDuration);

            return $interval->d * 1440 + $interval->h * 60 + $interval->i;
        } catch (\Throwable) {
            return (int) $segments[0]->departsAt->diffInMinutes(end($segments)->arrivesAt);
        }
    }
}
