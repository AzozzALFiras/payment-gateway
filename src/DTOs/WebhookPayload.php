<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\DTOs;

/**
 * Webhook payload DTO — normalized representation of a webhook event.
 */
final class WebhookPayload
{
    /**
     * @param array<string, mixed> $rawPayload
     */
    public function __construct(
        public readonly bool $isValid,
        public readonly string $event = '',
        public readonly string $transactionId = '',
        public readonly string $status = '',
        public readonly float $amount = 0.0,
        public readonly string $currency = '',
        public readonly string $orderId = '',
        public readonly string $message = '',
        public readonly array $rawPayload = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            isValid:       (bool) ($data['is_valid'] ?? false),
            event:         (string) ($data['event'] ?? ''),
            transactionId: (string) ($data['transaction_id'] ?? ''),
            status:        (string) ($data['status'] ?? ''),
            amount:        (float) ($data['amount'] ?? 0),
            currency:      (string) ($data['currency'] ?? ''),
            orderId:       (string) ($data['order_id'] ?? ''),
            message:       (string) ($data['message'] ?? ''),
            rawPayload:    (array) ($data['raw_payload'] ?? $data),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'is_valid'       => $this->isValid,
            'event'          => $this->event,
            'transaction_id' => $this->transactionId,
            'status'         => $this->status,
            'amount'         => $this->amount,
            'currency'       => $this->currency,
            'order_id'       => $this->orderId,
            'message'        => $this->message,
            'raw_payload'    => $this->rawPayload,
        ];
    }
}
