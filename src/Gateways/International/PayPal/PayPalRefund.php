<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\International\PayPal;

use AzozzALFiras\PaymentGateway\Http\HttpClient;

/**
 * PayPal Refund Sub-module.
 *
 * @link https://developer.paypal.com/docs/api/payments/v2/#captures_refund
 */
class PayPalRefund
{
    public function __construct(
        protected HttpClient $http,
        protected string $baseUrl,
        protected PayPalAuth $auth
    ) {}

    /**
     * Refund a captured payment.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(string $captureId, array $payload = []): array
    {
        $this->auth->authenticate();

        return $this->http->post($this->baseUrl . "/v2/payments/captures/{$captureId}/refund", $payload);
    }

    /**
     * Show refund details.
     *
     * @return array<string, mixed>
     */
    public function retrieve(string $refundId): array
    {
        $this->auth->authenticate();

        return $this->http->get($this->baseUrl . "/v2/payments/refunds/{$refundId}");
    }
}
