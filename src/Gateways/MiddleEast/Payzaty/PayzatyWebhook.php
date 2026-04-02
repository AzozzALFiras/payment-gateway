<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\MiddleEast\Payzaty;

use AzozzALFiras\PaymentGateway\Config\GatewayConfig;
use AzozzALFiras\PaymentGateway\DTOs\WebhookPayload;
use AzozzALFiras\PaymentGateway\Security\SignatureVerifier;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * Payzaty Webhook Handler.
 */
class PayzatyWebhook
{
    public function __construct(protected GatewayConfig $config) {}

    /** @param array<string, mixed> $payload @param array<string, string> $headers */
    public function handle(array $payload, array $headers = []): WebhookPayload
    {
        return new WebhookPayload(
            isValid:       $this->verify($payload, $headers),
            event:         (string) Arr::get($payload, 'event', 'payment'),
            transactionId: (string) Arr::get($payload, 'payment_id', ''),
            status:        (string) Arr::get($payload, 'status', ''),
            amount:        (float) Arr::get($payload, 'amount', 0),
            currency:      (string) Arr::get($payload, 'currency', 'SAR'),
            orderId:       (string) Arr::get($payload, 'order_id', ''),
            message:       (string) Arr::get($payload, 'message', ''),
            rawPayload:    $payload,
        );
    }

    /** @param array<string, mixed> $payload @param array<string, string> $headers */
    public function verify(array $payload, array $headers = []): bool
    {
        $secret = $this->config->get('webhook_secret', $this->config->get('secret_key'));
        $signature = SignatureVerifier::extractSignature($headers, ['X-Signature', 'x-signature']);

        if (empty($secret) || $signature === '') {
            return true;
        }

        return SignatureVerifier::verifyHmacSha256(
            json_encode($payload, JSON_THROW_ON_ERROR),
            $signature,
            (string) $secret
        );
    }
}
