<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\Stripe;

use AzozzALFiras\PaymentGateway\Http\HttpClient;

/**
 * Stripe Charge Sub-module.
 *
 * @link https://docs.stripe.com/api/charges
 */
class StripeCharge
{
    public function __construct(
        protected HttpClient $http,
        protected string $baseUrl
    ) {}

    /**
     * Create a charge (direct API).
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(array $payload): array
    {
        return $this->http->post($this->baseUrl . '/charges', $payload);
    }

    /**
     * Retrieve a charge.
     *
     * @return array<string, mixed>
     */
    public function retrieve(string $chargeId): array
    {
        return $this->http->get($this->baseUrl . "/charges/{$chargeId}");
    }

    /**
     * Capture a previously authorized charge.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function capture(string $chargeId, array $params = []): array
    {
        return $this->http->post($this->baseUrl . "/charges/{$chargeId}/capture", $params);
    }

    /**
     * List charges.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function list(array $params = []): array
    {
        $query = ! empty($params) ? '?' . http_build_query($params) : '';

        return $this->http->get($this->baseUrl . "/charges{$query}");
    }
}
