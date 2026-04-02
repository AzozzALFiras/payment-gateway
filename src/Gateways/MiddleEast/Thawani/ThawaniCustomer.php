<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\MiddleEast\Thawani;

use AzozzALFiras\PaymentGateway\Http\HttpClient;

/**
 * Thawani Customer Sub-module.
 */
class ThawaniCustomer
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

    /** @return array<string, mixed> */
    public function delete(string $customerId): array
    {
        return $this->http->delete($this->baseUrl . "/customers/{$customerId}");
    }

    /** @return array<string, mixed> */
    public function getPaymentMethods(string $customerId): array
    {
        return $this->http->get($this->baseUrl . "/payment_methods?customer_id={$customerId}");
    }
}
