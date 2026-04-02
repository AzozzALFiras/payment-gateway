<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\MiddleEast\Thawani;

use AzozzALFiras\PaymentGateway\Http\HttpClient;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * Thawani Checkout Session Sub-module.
 */
class ThawaniSession
{
    public function __construct(
        protected HttpClient $http,
        protected string $baseUrl,
        protected string $checkoutBaseUrl,
        protected string $publishableKey
    ) {}

    /**
     * Create a checkout session.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(array $payload): array
    {
        return $this->http->post($this->baseUrl . '/checkout/session', $payload);
    }

    /**
     * Retrieve a checkout session.
     *
     * @return array<string, mixed>
     */
    public function retrieve(string $sessionId): array
    {
        return $this->http->get($this->baseUrl . "/checkout/session/{$sessionId}");
    }

    /**
     * Build the checkout redirect URL from a session ID.
     */
    public function getCheckoutUrl(string $sessionId): string
    {
        return $this->checkoutBaseUrl . $sessionId . '?key=' . $this->publishableKey;
    }
}
