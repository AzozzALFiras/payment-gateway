<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\Paylink;

use AzozzALFiras\PaymentGateway\Exceptions\AuthenticationException;
use AzozzALFiras\PaymentGateway\Http\HttpClient;

/**
 * Paylink Authentication — manages token lifecycle.
 *
 * Tokens are obtained via POST /api/auth and cached for reuse.
 *
 * @link https://developer.paylink.sa/docs/authentication
 */
class PaylinkAuth
{
    private ?string $token = null;
    private ?int $tokenExpiresAt = null;

    public function __construct(
        private readonly HttpClient $http,
        private readonly string $baseUrl,
        private readonly string $apiId,
        private readonly string $secretKey,
        private readonly bool $persistToken = true,
    ) {
    }

    /**
     * Get a valid authentication token (auto-refreshes if expired).
     *
     * @throws AuthenticationException
     */
    public function getToken(): string
    {
        if ($this->persistToken && $this->token !== null && ! $this->isExpired()) {
            return $this->token;
        }

        return $this->authenticate();
    }

    /**
     * Authenticate with Paylink and obtain a new token.
     *
     * @throws AuthenticationException
     */
    public function authenticate(): string
    {
        $response = $this->http->post(
            $this->baseUrl . '/api/auth',
            [
                'apiId'     => $this->apiId,
                'secretKey' => $this->secretKey,
                'persistToken' => $this->persistToken,
            ]
        );

        $token = $response['id_token'] ?? null;

        if (empty($token)) {
            throw new AuthenticationException(
                'Paylink authentication failed: no token returned',
                0,
                null,
                $response
            );
        }

        $this->token = (string) $token;
        // Token generally expires in 30 minutes; refresh 2 minutes early
        $this->tokenExpiresAt = time() + (28 * 60);

        return $this->token;
    }

    /**
     * Check if the current token is expired.
     */
    public function isExpired(): bool
    {
        if ($this->tokenExpiresAt === null) {
            return true;
        }

        return time() >= $this->tokenExpiresAt;
    }

    /**
     * Clear the cached token.
     */
    public function clearToken(): void
    {
        $this->token = null;
        $this->tokenExpiresAt = null;
    }
}
