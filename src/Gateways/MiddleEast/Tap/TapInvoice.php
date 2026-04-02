<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\Tap;

use AzozzALFiras\PaymentGateway\Http\HttpClient;

/**
 * Tap Invoice Sub-module.
 *
 * @link https://developers.tap.company/reference/invoices
 */
class TapInvoice
{
    public function __construct(
        protected HttpClient $http,
        protected string $baseUrl
    ) {}

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function create(array $data): array
    {
        return $this->http->post($this->baseUrl . '/invoices', $data);
    }

    /** @return array<string, mixed> */
    public function retrieve(string $invoiceId): array
    {
        return $this->http->get($this->baseUrl . "/invoices/{$invoiceId}");
    }

    /** @return array<string, mixed> */
    public function finalize(string $invoiceId): array
    {
        return $this->http->post($this->baseUrl . "/invoices/{$invoiceId}/finalize");
    }
}
