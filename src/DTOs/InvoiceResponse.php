<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\DTOs;

/**
 * Invoice response DTO.
 */
final class InvoiceResponse
{
    /**
     * @param array<string, mixed> $rawResponse
     */
    public function __construct(
        public readonly bool $success,
        public readonly string $invoiceId = '',
        public readonly string $transactionNo = '',
        public readonly string $status = '',
        public readonly string $message = '',
        public readonly float $amount = 0.0,
        public readonly string $currency = '',
        public readonly string $paymentUrl = '',
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
            invoiceId:     (string) ($data['invoice_id'] ?? ''),
            transactionNo: (string) ($data['transaction_no'] ?? ''),
            status:        (string) ($data['status'] ?? ''),
            message:       (string) ($data['message'] ?? ''),
            amount:        (float) ($data['amount'] ?? 0),
            currency:      (string) ($data['currency'] ?? ''),
            paymentUrl:    (string) ($data['payment_url'] ?? ''),
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
            'invoice_id'     => $this->invoiceId,
            'transaction_no' => $this->transactionNo,
            'status'         => $this->status,
            'message'        => $this->message,
            'amount'         => $this->amount,
            'currency'       => $this->currency,
            'payment_url'    => $this->paymentUrl,
            'raw_response'   => $this->rawResponse,
        ];
    }
}
