<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\Stripe;

use AzozzALFiras\PaymentGateway\Http\HttpClient;

/**
 * Stripe Customer Sub-module.
 *
 * @link https://docs.stripe.com/api/customers
 */
class StripeCustomer
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
        return $this->http->post($this->baseUrl . "/customers/{$customerId}", $data);
    }

    /** @return array<string, mixed> */
    public function delete(string $customerId): array
    {
        return $this->http->delete($this->baseUrl . "/customers/{$customerId}");
    }

    /** @param array<string, mixed> $params @return array<string, mixed> */
    public function list(array $params = []): array
    {
        $query = ! empty($params) ? '?' . http_build_query($params) : '';

        return $this->http->get($this->baseUrl . "/customers{$query}");
    }
}
