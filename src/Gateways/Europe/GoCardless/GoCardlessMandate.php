<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\Europe\GoCardless;

use AzozzALFiras\PaymentGateway\Http\HttpClient;

/**
 * GoCardless Mandates Sub-module.
 *
 * Direct Debit mandates allow recurring payments.
 *
 * @link https://developer.gocardless.com/api-reference/
 */
class GoCardlessMandate
{
    public function __construct(
        protected HttpClient $http,
        protected string $baseUrl
    ) {}

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function create(array $data): array
    {
        return $this->http->post($this->baseUrl . '/mandates', ['mandates' => $data]);
    }

    /** @return array<string, mixed> */
    public function retrieve(string $mandateId): array
    {
        return $this->http->get($this->baseUrl . "/mandates/{$mandateId}");
    }

    /** @param array<string, mixed> $params @return array<string, mixed> */
    public function list(array $params = []): array
    {
        $query = ! empty($params) ? '?' . http_build_query($params) : '';

        return $this->http->get($this->baseUrl . "/mandates{$query}");
    }

    /** @return array<string, mixed> */
    public function cancel(string $mandateId): array
    {
        return $this->http->post($this->baseUrl . "/mandates/{$mandateId}/actions/cancel", []);
    }
}
