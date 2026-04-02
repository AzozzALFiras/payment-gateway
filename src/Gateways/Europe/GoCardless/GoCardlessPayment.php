<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\Europe\GoCardless;

use AzozzALFiras\PaymentGateway\Http\HttpClient;

/**
 * GoCardless Payments Sub-module.
 *
 * @link https://developer.gocardless.com/api-reference/
 */
class GoCardlessPayment
{
    public function __construct(
        protected HttpClient $http,
        protected string $baseUrl
    ) {}

    /**
     * Create a payment against a mandate.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(array $payload): array
    {
        return $this->http->post($this->baseUrl . '/payments', ['payments' => $payload]);
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
        return $this->http->post($this->baseUrl . "/payments/{$paymentId}/actions/cancel", []);
    }

    /**
     * Retry a failed payment.
     *
     * @return array<string, mixed>
     */
    public function retry(string $paymentId): array
    {
        return $this->http->post($this->baseUrl . "/payments/{$paymentId}/actions/retry", []);
    }
}
