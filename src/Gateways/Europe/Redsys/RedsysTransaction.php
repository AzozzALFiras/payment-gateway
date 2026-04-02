<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\Europe\Redsys;

use AzozzALFiras\PaymentGateway\Http\HttpClient;

/**
 * Redsys Transaction Sub-module.
 *
 * Handles payment initiation and REST API operations.
 *
 * @link https://redsys.es
 */
class RedsysTransaction
{
    public function __construct(
        protected HttpClient $http,
        protected string $baseUrl
    ) {}

    /**
     * Send a REST API request to Redsys.
     *
     * @param array<string, mixed> $payload  Signed merchant parameters
     * @return array<string, mixed>
     */
    public function initiatePayment(array $payload): array
    {
        return $this->http->postForm($this->baseUrl . '/realizarPago', $payload);
    }

    /**
     * Process a REST API operation (refund, auth, etc.).
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function restRequest(array $payload): array
    {
        return $this->http->post($this->baseUrl . '/rest/trataPeticionREST', $payload);
    }
}
