<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\Tap;

use AzozzALFiras\PaymentGateway\Http\HttpClient;

/**
 * Tap Customer Sub-module.
 *
 * @link https://developers.tap.company/reference/customers
 */
class TapCustomer
{
    public function __construct(
        protected HttpClient $http,
        protected string $baseUrl
    ) {}

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function create(array $data): array
    {
        return $this->http->post($this->baseUrl . '/customers', $data);
    }

    /** @return array<string, mixed> */
    public function retrieve(string $customerId): array
    {
        return $this->http->get($this->baseUrl . "/customers/{$customerId}");
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function update(string $customerId, array $data): array
    {
        return $this->http->put($this->baseUrl . "/customers/{$customerId}", $data);
    }

    /** @return array<string, mixed> */
    public function delete(string $customerId): array
    {
        return $this->http->delete($this->baseUrl . "/customers/{$customerId}");
    }

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    public function list(array $filters = []): array
    {
        return $this->http->post($this->baseUrl . '/customers/list', $filters);
    }
}
