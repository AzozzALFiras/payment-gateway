<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\PayPal;

use AzozzALFiras\PaymentGateway\Http\HttpClient;

/**
 * PayPal OAuth 2.0 Authentication Sub-module.
 *
 * Handles client_credentials token generation for API access.
 *
 * @link https://developer.paypal.com/api/rest/authentication/
 */
class PayPalAuth
{
    private ?string $accessToken = null;
    private int $expiresAt = 0;

    public function __construct(
        protected HttpClient $http,
        protected string $baseUrl,
        protected string $clientId,
        protected string $clientSecret
    ) {}

    /**
     * Get a valid access token, refreshing if expired.
     */
    public function getAccessToken(): string
    {
        if ($this->accessToken !== null && time() < $this->expiresAt) {
            return $this->accessToken;
        }

        $response = $this->http->postForm($this->baseUrl . '/v1/oauth2/token', [
            'grant_type' => 'client_credentials',
        ], [
            'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
            'Content-Type'  => 'application/x-www-form-urlencoded',
            'Accept'        => 'application/json',
        ]);

        $this->accessToken = (string) ($response['access_token'] ?? '');
        $this->expiresAt = time() + (int) ($response['expires_in'] ?? 3600) - 60;

        return $this->accessToken;
    }

    /**
     * Apply Bearer token to the HTTP client.
     */
    public function authenticate(): void
    {
        $this->http->setBearerToken($this->getAccessToken());
    }
}
