<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Contracts;

use AzozzALFiras\PaymentGateway\DTOs\WebhookPayload;

/**
 * Gateways implementing this interface support webhook handling.
 */
interface SupportsWebhook
{
    /**
     * Parse and validate an incoming webhook payload.
     *
     * @param array<string, mixed> $payload  The raw webhook data
     * @param array<string, string> $headers The request headers
     * @return WebhookPayload
     */
    public function handleWebhook(array $payload, array $headers = []): WebhookPayload;

    /**
     * Verify the webhook signature/authenticity.
     *
     * @param array<string, mixed> $payload
     * @param array<string, string> $headers
     * @return bool
     */
    public function verifyWebhook(array $payload, array $headers = []): bool;
}
