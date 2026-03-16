<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\Tap;

use AzozzALFiras\PaymentGateway\Http\HttpClient;

/**
 * Tap Token Sub-module — Card tokenization.
 *
 * @link https://developers.tap.company/reference/tokens
 */
class TapToken
{
    public function __construct(
        protected HttpClient $http,
        protected string $baseUrl
    ) {}

    /**
     * Create a Token (tokenize a card).
     *
     * @param array<string, mixed> $cardData Card details for tokenization
     * @return array<string, mixed>
     */
    public function create(array $cardData): array
    {
        return $this->http->post($this->baseUrl . '/tokens', $cardData);
    }
}
