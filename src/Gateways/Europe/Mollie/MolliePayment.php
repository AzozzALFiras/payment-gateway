<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\Europe\Mollie;

use AzozzALFiras\PaymentGateway\Http\HttpClient;

/**
 * Mollie Payments Sub-module.
 *
 * @link https://docs.mollie.com/reference/payments-api
 */
class MolliePayment
{
    public function __construct(
        protected HttpClient $http,
        protected string $baseUrl
    ) {}

    /**
     * Create a payment.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(array $payload): array
    {
        return $this->http->post($this->baseUrl . '/payments', $payload);
    }

    /**
     * Get a payment by ID.
     *
     * @return array<string, mixed>
     */
    public function retrieve(string $paymentId): array
    {
        return $this->http->get($this->baseUrl . "/payments/{$paymentId}");
    }

    /**
     * List payments.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function list(array $params = []): array
    {
        $query = ! empty($params) ? '?' . http_build_query($params) : '';

        return $this->http->get($this->baseUrl . "/payments{$query}");
    }

    /**
     * Cancel a payment.
     *
     * @return array<string, mixed>
     */
    public function cancel(string $paymentId): array
    {
        return $this->http->delete($this->baseUrl . "/payments/{$paymentId}");
    }
}
