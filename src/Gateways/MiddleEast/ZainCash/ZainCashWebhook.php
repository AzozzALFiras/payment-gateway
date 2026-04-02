<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\MiddleEast\ZainCash;

use AzozzALFiras\PaymentGateway\Config\GatewayConfig;
use AzozzALFiras\PaymentGateway\DTOs\WebhookPayload;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * ZainCash Webhook/Callback Handler.
 *
 * ZainCash redirects back with a transaction ID token that can be decoded.
 *
 * @link https://docs.zaincash.iq
 */
class ZainCashWebhook
{
    public function __construct(protected GatewayConfig $config) {}

    /**
     * @param array<string, mixed>  $payload
     * @param array<string, string> $headers
     */
    public function handle(array $payload, array $headers = []): WebhookPayload
    {
        $status = strtolower((string) Arr::get($payload, 'status', ''));

        $normalizedStatus = match ($status) {
            'completed', 'success' => 'paid',
            'pending', 'pending_otp' => 'pending',
            'failed'   => 'failed',
            'cancel'   => 'cancelled',
            default    => $status,
        };

        return new WebhookPayload(
            isValid:       $this->verify($payload, $headers),
            event:         'payment.' . $normalizedStatus,
            transactionId: (string) Arr::get($payload, 'id', ''),
            status:        $normalizedStatus,
            amount:        (float) Arr::get($payload, 'amount', 0),
            currency:      'IQD',
            orderId:       (string) Arr::get($payload, 'orderId', Arr::get($payload, 'orderid', '')),
            message:       (string) Arr::get($payload, 'msg', ''),
            rawPayload:    $payload,
        );
    }

    /**
     * @param array<string, mixed>  $payload
     * @param array<string, string> $headers
     */
    public function verify(array $payload, array $headers = []): bool
    {
        $secret = $this->config->get('secret');
        if (empty($secret)) {
            return true;
        }

        // ZainCash returns a JWT token in the callback; decode and verify
        $token = (string) Arr::get($payload, 'token', '');
        if ($token === '') {
            return ! empty($payload);
        }

        // Verify JWT by decoding with HS256
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        $headerB64 = $parts[0];
        $payloadB64 = $parts[1];
        $signatureB64 = $parts[2];

        $expectedSig = rtrim(strtr(
            base64_encode(hash_hmac('sha256', $headerB64 . '.' . $payloadB64, (string) $secret, true)),
            '+/',
            '-_'
        ), '=');

        return hash_equals($expectedSig, $signatureB64);
    }
}
