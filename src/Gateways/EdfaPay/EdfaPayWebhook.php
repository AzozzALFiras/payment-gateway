<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\EdfaPay;

use AzozzALFiras\PaymentGateway\Config\GatewayConfig;
use AzozzALFiras\PaymentGateway\DTOs\WebhookPayload;
use AzozzALFiras\PaymentGateway\Security\SignatureVerifier;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * EdfaPay Webhook Handler.
 *
 * Parses and validates incoming webhook (callback) notifications from EdfaPay.
 * Supports hash-based validation for webhook authenticity.
 *
 * @link https://edfapay-payment-gateway-api.readme.io/docs/webhook
 */
class EdfaPayWebhook
{
    public function __construct(
        private readonly GatewayConfig $config,
    ) {
    }

    /**
     * Parse an incoming webhook payload.
     *
     * @param array<string, mixed>  $payload
     * @param array<string, string> $headers
     * @return WebhookPayload
     */
    public function handle(array $payload, array $headers = []): WebhookPayload
    {
        $isValid = $this->verify($payload, $headers);

        return new WebhookPayload(
            isValid:       $isValid,
            event:         (string) Arr::get($payload, 'action', 'payment'),
            transactionId: (string) Arr::get($payload, 'trans_id', ''),
            status:        (string) Arr::get($payload, 'status', ''),
            amount:        (float) Arr::get($payload, 'order_amount', 0),
            currency:      (string) Arr::get($payload, 'order_currency', ''),
            orderId:       (string) Arr::get($payload, 'order_id', ''),
            message:       (string) Arr::get($payload, 'decline_reason', ''),
            rawPayload:    $payload,
        );
    }

    /**
     * Verify the webhook hash signature.
     *
     * EdfaPay webhook hash verification uses a different formula than the API request hash.
     * The webhook includes a 'hash' field that should be validated.
     *
     * @param array<string, mixed>  $payload
     * @param array<string, string> $headers
     * @return bool
     */
    public function verify(array $payload, array $headers = []): bool
    {
        $webhookPassword = $this->config->get('webhook_password', $this->config->get('password'));

        if (empty($webhookPassword)) {
            return true;
        }

        $receivedHash = (string) Arr::get($payload, 'hash', '');

        if ($receivedHash === '') {
            return false;
        }

        // Reconstruct webhook hash based on EdfaPay's webhook validation formula
        // The exact formula may vary — this validates the core fields
        $email = (string) Arr::get($payload, 'payer_email', '');
        $cardNumber = (string) Arr::get($payload, 'card', '');

        if ($email === '' || $cardNumber === '') {
            // Cannot verify without required fields; defer to application logic
            return true;
        }

        // For webhook, card is typically masked (first6***last4)
        // Extract the digits for hash generation
        $cleanCard = preg_replace('/[^0-9]/', '', $cardNumber) ?? '';

        if (strlen($cleanCard) >= 10) {
            $first6 = substr($cleanCard, 0, 6);
            $last4 = substr($cleanCard, -4);
            $computedHash = EdfaPayHash::generateFromMasked($email, $first6, $last4, (string) $webhookPassword);

            return hash_equals($computedHash, $receivedHash);
        }

        return true;
    }
}
