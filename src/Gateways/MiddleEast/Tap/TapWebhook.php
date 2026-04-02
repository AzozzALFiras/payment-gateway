<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\Tap;

use AzozzALFiras\PaymentGateway\Config\GatewayConfig;
use AzozzALFiras\PaymentGateway\DTOs\WebhookPayload;
use AzozzALFiras\PaymentGateway\Security\SignatureVerifier;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * Tap Webhook Handler.
 */
class TapWebhook
{
    public function __construct(protected GatewayConfig $config) {}

    /**
     * Parse and verify incoming webhook.
     *
     * @param array<string, mixed>  $payload
     * @param array<string, string> $headers
     */
    public function handle(array $payload, array $headers = []): WebhookPayload
    {
        $isValid = $this->verify($payload, $headers);

        return new WebhookPayload(
            isValid:       $isValid,
            event:         (string) Arr::get($payload, 'event', 'charge'),
            transactionId: (string) Arr::get($payload, 'id', ''),
            status:        (string) Arr::get($payload, 'status', ''),
            amount:        (float) Arr::get($payload, 'amount', 0),
            currency:      (string) Arr::get($payload, 'currency', ''),
            orderId:       (string) Arr::get($payload, 'reference.order', ''),
            message:       (string) Arr::get($payload, 'response.message', ''),
            rawPayload:    $payload,
        );
    }

    /**
     * Verify the webhook signature.
     *
     * @param array<string, mixed>  $payload
     * @param array<string, string> $headers
     */
    public function verify(array $payload, array $headers = []): bool
    {
        $webhookSecret = $this->config->get('webhook_secret');

        if (empty($webhookSecret)) {
            return true;
        }

        $signature = SignatureVerifier::extractSignature($headers, ['Tap-Signature', 'tap-signature']);

        if ($signature === '') {
            return false;
        }

        return SignatureVerifier::verifyHmacSha256(
            json_encode($payload, JSON_THROW_ON_ERROR),
            $signature,
            (string) $webhookSecret
        );
    }
}
