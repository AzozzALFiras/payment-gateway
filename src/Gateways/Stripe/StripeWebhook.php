<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\Stripe;

use AzozzALFiras\PaymentGateway\Config\GatewayConfig;
use AzozzALFiras\PaymentGateway\DTOs\WebhookPayload;
use AzozzALFiras\PaymentGateway\Security\SignatureVerifier;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * Stripe Webhook Handler.
 *
 * Verifies Stripe-Signature header using HMAC-SHA256.
 *
 * @link https://docs.stripe.com/webhooks/signatures
 */
class StripeWebhook
{
    public function __construct(protected GatewayConfig $config) {}

    /**
     * @param array<string, mixed>  $payload
     * @param array<string, string> $headers
     */
    public function handle(array $payload, array $headers = []): WebhookPayload
    {
        $data = (array) Arr::get($payload, 'data.object', []);
        $eventType = (string) Arr::get($payload, 'type', '');

        $status = match (true) {
            str_contains($eventType, 'succeeded')  => 'paid',
            str_contains($eventType, 'failed')     => 'failed',
            str_contains($eventType, 'canceled')   => 'cancelled',
            str_contains($eventType, 'refunded')   => 'refunded',
            str_contains($eventType, 'expired')    => 'expired',
            str_contains($eventType, 'processing') => 'pending',
            default => (string) Arr::get($data, 'status', $eventType),
        };

        return new WebhookPayload(
            isValid:       $this->verify($payload, $headers),
            event:         $eventType,
            transactionId: (string) Arr::get($data, 'id', Arr::get($data, 'payment_intent', '')),
            status:        $status,
            amount:        ((float) Arr::get($data, 'amount_total', Arr::get($data, 'amount', 0))) / 100,
            currency:      strtoupper((string) Arr::get($data, 'currency', '')),
            orderId:       (string) Arr::get($data, 'client_reference_id', Arr::get($data, 'metadata.order_id', '')),
            message:       (string) Arr::get($data, 'description', ''),
            rawPayload:    $payload,
        );
    }

    /**
     * @param array<string, mixed>  $payload
     * @param array<string, string> $headers
     */
    public function verify(array $payload, array $headers = []): bool
    {
        $secret = $this->config->get('webhook_secret');
        if (empty($secret)) {
            return true;
        }

        $signatureHeader = SignatureVerifier::extractSignature($headers, ['Stripe-Signature', 'stripe-signature']);
        if ($signatureHeader === '') {
            return false;
        }

        // Parse Stripe-Signature: t=timestamp,v1=signature
        $parts = [];
        foreach (explode(',', $signatureHeader) as $part) {
            [$key, $value] = explode('=', trim($part), 2);
            $parts[$key] = $value;
        }

        $timestamp = $parts['t'] ?? '';
        $v1Signature = $parts['v1'] ?? '';

        if ($timestamp === '' || $v1Signature === '') {
            return false;
        }

        $payloadString = json_encode($payload, JSON_THROW_ON_ERROR);
        $signedPayload = $timestamp . '.' . $payloadString;

        return SignatureVerifier::verifyHmacSha256($signedPayload, $v1Signature, (string) $secret);
    }
}
