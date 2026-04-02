<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\International\Stripe;

use AzozzALFiras\PaymentGateway\Http\HttpClient;

/**
 * Stripe Checkout Session Sub-module.
 *
 * @link https://docs.stripe.com/api/checkout/sessions
 */
class StripeCheckout
{
    public function __construct(
        protected HttpClient $http,
        protected string $baseUrl
    ) {}

    /**
     * Create a checkout session.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(array $payload): array
    {
        return $this->http->post($this->baseUrl . '/checkout/sessions', $payload);
    }

    /**
     * Retrieve a checkout session.
     *
     * @return array<string, mixed>
     */
    public function retrieve(string $sessionId): array
    {
        return $this->http->get($this->baseUrl . "/checkout/sessions/{$sessionId}");
    }

    /**
     * List line items for a checkout session.
     *
     * @return array<string, mixed>
     */
    public function listLineItems(string $sessionId, array $params = []): array
    {
        $query = ! empty($params) ? '?' . http_build_query($params) : '';

        return $this->http->get($this->baseUrl . "/checkout/sessions/{$sessionId}/line_items{$query}");
    }

    /**
     * Expire a session (cancel).
     *
     * @return array<string, mixed>
     */
    public function expire(string $sessionId): array
    {
        return $this->http->post($this->baseUrl . "/checkout/sessions/{$sessionId}/expire", []);
    }
}
