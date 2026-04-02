<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\MiddleEast\Thawani;

use AzozzALFiras\PaymentGateway\Config\GatewayConfig;
use AzozzALFiras\PaymentGateway\DTOs\WebhookPayload;
use AzozzALFiras\PaymentGateway\Security\SignatureVerifier;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * Thawani Webhook Handler.
 */
class ThawaniWebhook
{
    public function __construct(protected GatewayConfig $config) {}

    /** @param array<string, mixed> $payload @param array<string, string> $headers */
    public function handle(array $payload, array $headers = []): WebhookPayload
    {
        $data = (array) Arr::get($payload, 'data', $payload);

        return new WebhookPayload(
            isValid:       $this->verify($payload, $headers),
            event:         (string) Arr::get($payload, 'event', 'checkout'),
            transactionId: (string) Arr::get($data, 'session_id', ''),
            status:        (string) Arr::get($data, 'payment_status', ''),
            amount:        (float) (Arr::get($data, 'total_amount', 0)) / 1000,
            currency:      'OMR',
            orderId:       (string) Arr::get($data, 'client_reference_id', ''),
            rawPayload:    $payload,
        );
    }

    /** @param array<string, mixed> $payload @param array<string, string> $headers */
    public function verify(array $payload, array $headers = []): bool
    {
        $secret = $this->config->get('webhook_secret');
        if (empty($secret)) {
            return true;
        }

        $signature = SignatureVerifier::extractSignature($headers, ['Thawani-Signature', 'thawani-signature']);
        if ($signature === '') {
            return false;
        }

        return SignatureVerifier::verifyHmacSha256(
            json_encode($payload, JSON_THROW_ON_ERROR),
            $signature,
            (string) $secret
        );
    }
}
