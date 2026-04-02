<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\Europe\GoCardless;

use AzozzALFiras\PaymentGateway\Http\HttpClient;

/**
 * GoCardless Refunds Sub-module.
 *
 * @link https://developer.gocardless.com/api-reference/
 */
class GoCardlessRefund
{
    public function __construct(
        protected HttpClient $http,
        protected string $baseUrl
    ) {}

    /**
     * Create a refund.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(array $payload): array
    {
        return $this->http->post($this->baseUrl . '/refunds', ['refunds' => $payload]);
    }

    /**
     * Get a refund by ID.
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
