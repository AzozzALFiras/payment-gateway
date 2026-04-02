<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\International\PayPal;

use AzozzALFiras\PaymentGateway\Http\HttpClient;

/**
 * PayPal Payments (Captures & Authorizations) Sub-module.
 *
 * @link https://developer.paypal.com/docs/api/payments/v2/
 */
class PayPalPayment
{
    public function __construct(
        protected HttpClient $http,
        protected string $baseUrl,
        protected PayPalAuth $auth
    ) {}

    /**
     * Show captured payment details.
     *
     * @return array<string, mixed>
     */
    public function showCapturedPayment(string $captureId): array
    {
        $this->auth->authenticate();

        return $this->http->get($this->baseUrl . "/v2/payments/captures/{$captureId}");
    }

    /**
     * Show authorization details.
     *
     * @return array<string, mixed>
     */
    public function showAuthorization(string $authorizationId): array
    {
        $this->auth->authenticate();

        return $this->http->get($this->baseUrl . "/v2/payments/authorizations/{$authorizationId}");
    }

    /**
     * Capture an authorized payment.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function captureAuthorization(string $authorizationId, array $payload = []): array
    {
        $this->auth->authenticate();

        return $this->http->post($this->baseUrl . "/v2/payments/authorizations/{$authorizationId}/capture", $payload);
    }

    /**
     * Void an authorized payment.
     *
     * @return array<string, mixed>
     */
    public function voidAuthorization(string $authorizationId): array
    {
        $this->auth->authenticate();

        return $this->http->post($this->baseUrl . "/v2/payments/authorizations/{$authorizationId}/void", []);
    }
}
