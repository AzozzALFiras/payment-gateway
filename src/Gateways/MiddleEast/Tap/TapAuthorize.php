<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\MiddleEast\Tap;

use AzozzALFiras\PaymentGateway\DTOs\PaymentResponse;
use AzozzALFiras\PaymentGateway\Http\HttpClient;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * Tap Authorize Sub-module.
 *
 * Handles authorization (pre-auth hold) and void operations.
 *
 * @link https://developers.tap.company/reference/authorize
 */
class TapAuthorize
{
    public function __construct(
        protected HttpClient $http,
        protected string $baseUrl
    ) {}

    /**
     * Create an Authorization.
     *
     * @param array<string, mixed> $params
     */
    public function create(array $params): PaymentResponse
    {
        $response = $this->http->post($this->baseUrl . '/authorize', $params);
        $status = (string) Arr::get($response, 'status', '');

        return new PaymentResponse(
            success:       in_array($status, ['AUTHORIZED', 'INITIATED'], true),
            transactionId: (string) Arr::get($response, 'id', ''),
            status:        $status,
            message:       (string) Arr::get($response, 'response.message', ''),
            amount:        (float) Arr::get($response, 'amount', 0),
            currency:      (string) Arr::get($response, 'currency', ''),
            paymentUrl:    (string) Arr::get($response, 'transaction.url', ''),
            rawResponse:   $response,
        );
    }

    /**
     * Retrieve an Authorization.
     *
     * @param string $authorizeId
     * @return array<string, mixed>
     */
    public function retrieve(string $authorizeId): array
    {
        return $this->http->get($this->baseUrl . "/authorize/{$authorizeId}");
    }

    /**
     * Update an Authorization.
     *
     * @param string               $authorizeId
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function update(string $authorizeId, array $data): array
    {
        return $this->http->put($this->baseUrl . "/authorize/{$authorizeId}", $data);
    }

    /**
     * Void an Authorization.
     */
    public function void(string $authorizeId): PaymentResponse
    {
        $response = $this->http->post($this->baseUrl . "/authorize/{$authorizeId}/void");

        return new PaymentResponse(
            success:       Arr::get($response, 'status') === 'VOID',
            transactionId: (string) Arr::get($response, 'id', $authorizeId),
            status:        (string) Arr::get($response, 'status', ''),
            message:       (string) Arr::get($response, 'response.message', ''),
            rawResponse:   $response,
        );
    }
}
