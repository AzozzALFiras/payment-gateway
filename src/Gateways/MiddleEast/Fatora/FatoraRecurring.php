<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\Fatora;

use AzozzALFiras\PaymentGateway\Http\HttpClient;

/**
 * Fatora Recurring (Card Tokenization) Sub-module.
 */
class FatoraRecurring
{
    public function __construct(
        protected HttpClient $http,
        protected string $baseUrl
    ) {}

    /**
     * Charge a saved card token.
     *
     * @param array<string, mixed> $payload  Must include 'token', 'amount', 'currency', 'order_id'
     * @return array<string, mixed>
     */
    public function charge(array $payload): array
    {
        $payload['trigger_transaction'] = true;

        return $this->http->post($this->baseUrl . '/v1/payments/checkout', $payload);
    }

    /**
     * Deactivate (revoke) a saved card token.
     *
     * @return array<string, mixed>
     */
    public function deactivateToken(string $token): array
    {
        return $this->http->post($this->baseUrl . '/v1/payments/token/deactivate', [
            'token' => $token,
        ]);
    }
}
