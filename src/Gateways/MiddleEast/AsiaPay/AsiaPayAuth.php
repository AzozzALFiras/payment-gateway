<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\MiddleEast\AsiaPay;

use AzozzALFiras\PaymentGateway\Http\HttpClient;

/**
 * AsiaPay JWT Authentication Sub-module.
 *
 * Handles token generation using appSecret + X-APP-Key.
 *
 * @link https://www.asiapay.iq/integration
 */
class AsiaPayAuth
{
    private ?string $token = null;
    private int $expiresAt = 0;

    public function __construct(
        protected HttpClient $http,
        protected string $baseUrl,
        protected string $appKey,
        protected string $appSecret,
        protected string $privateKey
    ) {}

    /**
     * Get a valid bearer token, refreshing if expired.
     */
    public function getToken(): string
    {
        if ($this->token !== null && time() < $this->expiresAt) {
            return $this->token;
        }

        $payload = json_encode(['appSecret' => $this->appSecret], JSON_THROW_ON_ERROR);

        // Sign the payload with private key (JWT)
        $sign = $this->signPayload($payload);

        $response = $this->http->post(
            $this->baseUrl . '/payment/gateway/payment/v1/token',
            ['appSecret' => $this->appSecret, 'sign' => $sign],
            ['X-APP-Key' => $this->appKey]
        );

        $this->token = (string) ($response['token'] ?? '');
        // Token valid for ~1 hour, refresh 5 minutes early
        $this->expiresAt = time() + 3300;

        return $this->token;
    }

    /**
     * Sign payload using HMAC-SHA256 with private key.
     */
    public function signPayload(string $payload): string
    {
        return hash_hmac('sha256', $payload, $this->privateKey);
    }

    /**
     * Apply authentication headers to HTTP client.
     */
    public function authenticate(): void
    {
        $token = $this->getToken();
        $this->http->setHeader('Authorization', $token);
        $this->http->setHeader('X-APP-Key', $this->appKey);
    }
}
