<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\PayPal;

use AzozzALFiras\PaymentGateway\Http\HttpClient;

/**
 * PayPal Orders API Sub-module.
 *
 * @link https://developer.paypal.com/docs/api/orders/v2/
 */
class PayPalOrder
{
    public function __construct(
        protected HttpClient $http,
        protected string $baseUrl,
        protected PayPalAuth $auth
    ) {}

    /**
     * Create an order.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(array $payload): array
    {
        $this->auth->authenticate();

        return $this->http->post($this->baseUrl . '/v2/checkout/orders', $payload);
    }

    /**
     * Retrieve an order by ID.
     *
     * @return array<string, mixed>
     */
    public function retrieve(string $orderId): array
    {
        $this->auth->authenticate();

        return $this->http->get($this->baseUrl . "/v2/checkout/orders/{$orderId}");
    }

    /**
     * Capture payment for an order.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function capture(string $orderId, array $payload = []): array
    {
        $this->auth->authenticate();

        return $this->http->post($this->baseUrl . "/v2/checkout/orders/{$orderId}/capture", $payload);
    }

    /**
     * Authorize payment for an order.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function authorize(string $orderId, array $payload = []): array
    {
        $this->auth->authenticate();

        return $this->http->post($this->baseUrl . "/v2/checkout/orders/{$orderId}/authorize", $payload);
    }
}
