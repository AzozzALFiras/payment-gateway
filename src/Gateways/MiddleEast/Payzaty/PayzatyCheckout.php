<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\Payzaty;

use AzozzALFiras\PaymentGateway\Http\HttpClient;

/**
 * Payzaty Checkout Sub-module.
 */
class PayzatyCheckout
{
    public function __construct(
        protected HttpClient $http,
        protected string $baseUrl
    ) {}

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(array $payload): array
    {
        return $this->http->post($this->baseUrl . '/checkout', $payload);
    }

    /** @return array<string, mixed> */
    public function status(string $paymentId): array
    {
        return $this->http->get($this->baseUrl . "/checkout/status/{$paymentId}");
    }
}
