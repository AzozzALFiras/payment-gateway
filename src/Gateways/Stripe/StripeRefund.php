<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\Stripe;

use AzozzALFiras\PaymentGateway\Http\HttpClient;

/**
 * Stripe Refund Sub-module.
 *
 * @link https://docs.stripe.com/api/refunds
 */
class StripeRefund
{
    public function __construct(
        protected HttpClient $http,
        protected string $baseUrl
    ) {}

    /**
     * Create a refund.
     *
     * @param array<string, mixed> $payload  Must include 'charge' or 'payment_intent'
     * @return array<string, mixed>
     */
    public function create(array $payload): array
    {
        return $this->http->post($this->baseUrl . '/refunds', $payload);
    }

    /**
     * Retrieve a refund.
     *
     * @return array<string, mixed>
     */
    public function retrieve(string $refundId): array
    {
        return $this->http->get($this->baseUrl . "/refunds/{$refundId}");
    }

    /**
     * List refunds.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function list(array $params = []): array
    {
        $query = ! empty($params) ? '?' . http_build_query($params) : '';

        return $this->http->get($this->baseUrl . "/refunds{$query}");
    }
}
