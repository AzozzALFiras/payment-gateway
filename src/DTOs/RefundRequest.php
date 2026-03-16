<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\DTOs;

/**
 * Refund request DTO.
 */
final class RefundRequest
{
    /**
     * @param array<string, mixed> $metadata Extra gateway-specific parameters
     */
    public function __construct(
        public readonly string $transactionId,
        public readonly float $amount,
        public readonly string $currency = 'SAR',
        public readonly string $reason = '',
        public readonly array $metadata = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            transactionId: (string) ($data['transaction_id'] ?? ''),
            amount:        (float) ($data['amount'] ?? 0),
            currency:      (string) ($data['currency'] ?? 'SAR'),
            reason:        (string) ($data['reason'] ?? ''),
            metadata:      (array) ($data['metadata'] ?? []),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'transaction_id' => $this->transactionId,
            'amount'         => $this->amount,
            'currency'       => $this->currency,
            'reason'         => $this->reason,
            'metadata'       => $this->metadata,
        ], fn(mixed $v) => $v !== '' && $v !== []);
    }
}
