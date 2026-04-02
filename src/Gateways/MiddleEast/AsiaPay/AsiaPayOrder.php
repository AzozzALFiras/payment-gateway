<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\MiddleEast\AsiaPay;

use AzozzALFiras\PaymentGateway\Http\HttpClient;

/**
 * AsiaPay Order Sub-module.
 *
 * Handles pre-order creation, query, and refund.
 *
 * @link https://www.asiapay.iq/integration
 */
class AsiaPayOrder
{
    public function __construct(
        protected HttpClient $http,
        protected string $baseUrl,
        protected AsiaPayAuth $auth
    ) {}

    /**
     * Create a pre-order (payment initiation).
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function preOrder(array $payload): array
    {
        $this->auth->authenticate();

        return $this->http->post(
            $this->baseUrl . '/payment/gateway/payment/v1/merchant/preOrder',
            $payload
        );
    }

    /**
     * Query an existing order by merchant order ID.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function queryOrder(array $payload): array
    {
        $this->auth->authenticate();

        return $this->http->post(
            $this->baseUrl . '/payment/gateway/payment/v1/merchant/queryOrder',
            $payload
        );
    }

    /**
     * Refund an order.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function refund(array $payload): array
    {
        $this->auth->authenticate();

        return $this->http->post(
            $this->baseUrl . '/payment/gateway/payment/v1/merchant/refund',
            $payload
        );
    }
}
