<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\DTOs;

/**
 * Universal payment response DTO.
 */
final class PaymentResponse
{
    /**
     * @param array<string, mixed> $rawResponse Complete raw API response
     */
    public function __construct(
        public readonly bool $success,
        public readonly string $transactionId = '',
        public readonly string $status = '',
        public readonly string $message = '',
        public readonly float $amount = 0.0,
        public readonly string $currency = '',
        public readonly string $paymentUrl = '',
        public readonly string $redirectUrl = '',
        public readonly ?string $sessionId = null,
        public readonly ?string $recurringToken = null,
        public readonly array $rawResponse = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            success:        (bool) ($data['success'] ?? false),
            transactionId:  (string) ($data['transaction_id'] ?? ''),
            status:         (string) ($data['status'] ?? ''),
            message:        (string) ($data['message'] ?? ''),
            amount:         (float) ($data['amount'] ?? 0),
            currency:       (string) ($data['currency'] ?? ''),
            paymentUrl:     (string) ($data['payment_url'] ?? ''),
            redirectUrl:    (string) ($data['redirect_url'] ?? ''),
            sessionId:      isset($data['session_id']) ? (string) $data['session_id'] : null,
            recurringToken: isset($data['recurring_token']) ? (string) $data['recurring_token'] : null,
            rawResponse:    (array) ($data['raw_response'] ?? $data),
        );
    }

    /**
     * Check if the payment was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->success;
    }

    /**
     * Check if the response contains a redirect URL.
     */
    public function requiresRedirect(): bool
    {
        return $this->paymentUrl !== '' || $this->redirectUrl !== '';
    }

    /**
     * Get the URL to redirect the customer to.
     */
    public function getRedirectUrl(): string
    {
        return $this->paymentUrl ?: $this->redirectUrl;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success'         => $this->success,
            'transaction_id'  => $this->transactionId,
            'status'          => $this->status,
            'message'         => $this->message,
            'amount'          => $this->amount,
            'currency'        => $this->currency,
            'payment_url'     => $this->paymentUrl,
            'redirect_url'    => $this->redirectUrl,
            'session_id'      => $this->sessionId,
            'recurring_token' => $this->recurringToken,
            'raw_response'    => $this->rawResponse,
        ];
    }
}
