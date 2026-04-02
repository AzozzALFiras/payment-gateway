<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\Europe\GoCardless;

use AzozzALFiras\PaymentGateway\Config\GatewayConfig;
use AzozzALFiras\PaymentGateway\DTOs\WebhookPayload;
use AzozzALFiras\PaymentGateway\Security\SignatureVerifier;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * GoCardless Webhook Handler.
 *
 * Verifies Webhook-Signature header using HMAC-SHA256.
 *
 * @link https://developer.gocardless.com/api-reference/
 */
class GoCardlessWebhook
{
    public function __construct(protected GatewayConfig $config) {}

    /**
     * @param array<string, mixed>  $payload
     * @param array<string, string> $headers
     */
    public function handle(array $payload, array $headers = []): WebhookPayload
    {
        $events = (array) Arr::get($payload, 'events', []);
        $event = ! empty($events) ? $events[0] : [];

        $action = (string) Arr::get($event, 'action', '');
        $resourceType = (string) Arr::get($event, 'resource_type', '');
        $links = (array) Arr::get($event, 'links', []);

        $status = match ($action) {
            'confirmed', 'paid_out'     => 'paid',
            'created', 'submitted'      => 'pending',
            'failed'                    => 'failed',
            'cancelled'                 => 'cancelled',
            'charged_back'              => 'refunded',
            'refund_created', 'refunded' => 'refunded',
            default                     => $action,
        };

        return new WebhookPayload(
            isValid:       $this->verify($payload, $headers),
            event:         $resourceType . '.' . $action,
            transactionId: (string) Arr::get($links, 'payment', Arr::get($event, 'id', '')),
            status:        $status,
            amount:        0.0,
            currency:      '',
            orderId:       (string) Arr::get($links, 'mandate', ''),
            message:       (string) Arr::get($event, 'details.description', ''),
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

        $signature = SignatureVerifier::extractSignature($headers, [
            'Webhook-Signature', 'webhook-signature',
        ]);

        if ($signature === '') {
            return false;
        }

        $payloadString = json_encode($payload, JSON_THROW_ON_ERROR);

        return SignatureVerifier::verifyHmacSha256($payloadString, $signature, (string) $secret);
    }
}
