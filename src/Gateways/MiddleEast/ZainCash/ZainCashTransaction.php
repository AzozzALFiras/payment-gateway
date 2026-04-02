<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\MiddleEast\ZainCash;

use AzozzALFiras\PaymentGateway\Http\HttpClient;

/**
 * ZainCash Transaction Sub-module.
 *
 * Handles transaction init and status retrieval.
 *
 * @link https://docs.zaincash.iq
 */
class ZainCashTransaction
{
    public function __construct(
        protected HttpClient $http,
        protected string $baseUrl
    ) {}

    /**
     * Initialize a new transaction.
     *
     * @param array<string, mixed> $payload  Must include 'token' (JWT encoded)
     * @return array<string, mixed>
     */
    public function init(array $payload): array
    {
        return $this->http->postForm($this->baseUrl . '/transaction/init', $payload);
    }

    /**
     * Get transaction status by ID.
     *
     * @param array<string, mixed> $payload  Must include 'id' and 'token' (JWT encoded)
     * @return array<string, mixed>
     */
    public function get(array $payload): array
    {
        return $this->http->postForm($this->baseUrl . '/transaction/get', $payload);
    }
}
