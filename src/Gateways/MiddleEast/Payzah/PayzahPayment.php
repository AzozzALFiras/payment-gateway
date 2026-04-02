<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\Payzah;

use AzozzALFiras\PaymentGateway\Http\HttpClient;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * Payzah Payment Sub-module.
 */
class PayzahPayment
{
    public function __construct(
        protected HttpClient $http,
        protected string $baseUrl,
        protected string $privateKey
    ) {}

    /**
     * Initialize a payment.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function init(array $payload): array
    {
        $payload['private_key'] = $this->privateKey;

        return $this->http->post($this->baseUrl . '/v1/payment/init', $payload);
    }

    /**
     * Check payment status.
     *
     * @return array<string, mixed>
     */
    public function status(string $paymentId): array
    {
        return $this->http->post($this->baseUrl . '/v1/payment/status', [
            'private_key' => $this->privateKey,
            'payment_id'  => $paymentId,
        ]);
    }
}
