<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\MiddleEast\Tamara;

use AzozzALFiras\PaymentGateway\Http\HttpClient;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * Tamara Checkout Sub-module.
 */
class TamaraCheckout
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
        return $this->http->post($this->baseUrl . '/checkout', $payload);
    }

    /**
     * Check available payment options for a given order.
     *
     * @param array<string, mixed> $orderData
     * @return array<string, mixed>
     */
    public function paymentOptions(array $orderData): array
    {
        return $this->http->post($this->baseUrl . '/checkout/payment-options-pre-check', $orderData);
    }
}
