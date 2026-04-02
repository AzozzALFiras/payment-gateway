<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\Europe\Mollie;

use AzozzALFiras\PaymentGateway\Http\HttpClient;

/**
 * Mollie Refunds Sub-module.
 *
 * @link https://docs.mollie.com/docs/refunds
 */
class MollieRefund
{
    public function __construct(
        protected HttpClient $http,
        protected string $baseUrl
    ) {}

    /**
     * Create a refund for a payment.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(string $paymentId, array $payload): array
    {
        return $this->http->post($this->baseUrl . "/payments/{$paymentId}/refunds", $payload);
    }

    /**
     * Get a specific refund.
     *
     * @return array<string, mixed>
     */
    public function retrieve(string $paymentId, string $refundId): array
    {
        return $this->http->get($this->baseUrl . "/payments/{$paymentId}/refunds/{$refundId}");
    }

    /**
     * List refunds for a payment.
     *
     * @return array<string, mixed>
     */
    public function list(string $paymentId): array
    {
        return $this->http->get($this->baseUrl . "/payments/{$paymentId}/refunds");
    }

    /**
     * Cancel a refund.
     *
     * @return array<string, mixed>
     */
    public function cancel(string $paymentId, string $refundId): array
    {
        return $this->http->delete($this->baseUrl . "/payments/{$paymentId}/refunds/{$refundId}");
    }
}
