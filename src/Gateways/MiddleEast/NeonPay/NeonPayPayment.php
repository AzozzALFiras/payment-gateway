<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\MiddleEast\NeonPay;

use AzozzALFiras\PaymentGateway\Http\HttpClient;

/**
 * NeonPay Payments Sub-module.
 *
 * Handles payment creation, retrieval, listing, and refunds.
 *
 * @link https://neonpay.com
 */
class NeonPayPayment
{
    public function __construct(
        protected HttpClient $http,
        protected string $baseUrl
    ) {}

    /**
     * Create a new payment.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(array $payload): array
    {
        return $this->http->post($this->baseUrl . '/api/v1/payments', $payload);
    }

    /**
     * Get payment status by token.
     *
     * @return array<string, mixed>
     */
    public function retrieve(string $paymentToken): array
    {
        return $this->http->get($this->baseUrl . "/api/v1/payments/{$paymentToken}");
    }

    /**
     * List all payments with pagination.
     *
     * @param array<string, mixed> $params  Filters: status, order_id, from, to, per_page, page
     * @return array<string, mixed>
     */
    public function list(array $params = []): array
    {
        $query = ! empty($params) ? '?' . http_build_query($params) : '';

        return $this->http->get($this->baseUrl . "/api/v1/payments{$query}");
    }

    /**
     * Refund a completed payment.
     *
     * @return array<string, mixed>
     */
    public function refund(string $paymentToken): array
    {
        return $this->http->post($this->baseUrl . "/api/v1/payments/{$paymentToken}/refund", []);
    }
}
