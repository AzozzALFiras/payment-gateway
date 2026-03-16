<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\DTOs;

/**
 * Refund response DTO.
 */
final class RefundResponse
{
    /**
     * @param array<string, mixed> $rawResponse
     */
    public function __construct(
        public readonly bool $success,
        public readonly string $refundId = '',
        public readonly string $transactionId = '',
        public readonly string $status = '',
        public readonly string $message = '',
        public readonly float $amount = 0.0,
        public readonly string $currency = '',
        public readonly array $rawResponse = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            success:       (bool) ($data['success'] ?? false),
            refundId:      (string) ($data['refund_id'] ?? ''),
            transactionId: (string) ($data['transaction_id'] ?? ''),
            status:        (string) ($data['status'] ?? ''),
            message:       (string) ($data['message'] ?? ''),
            amount:        (float) ($data['amount'] ?? 0),
            currency:      (string) ($data['currency'] ?? ''),
            rawResponse:   (array) ($data['raw_response'] ?? $data),
        );
    }

    public function isSuccessful(): bool
    {
        return $this->success;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success'        => $this->success,
            'refund_id'      => $this->refundId,
            'transaction_id' => $this->transactionId,
            'status'         => $this->status,
            'message'        => $this->message,
            'amount'         => $this->amount,
            'currency'       => $this->currency,
            'raw_response'   => $this->rawResponse,
        ];
    }
}
