<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\DTOs;

/**
 * Universal payment request DTO.
 */
final class PaymentRequest
{
    /**
     * @param array<int, array<string, mixed>> $items    Line items / products
     * @param array<string, mixed>             $metadata Extra gateway-specific parameters
     */
    public function __construct(
        public readonly float $amount,
        public readonly string $currency = 'SAR',
        public readonly string $orderId = '',
        public readonly string $description = '',
        public readonly string $callbackUrl = '',
        public readonly string $returnUrl = '',
        public readonly string $cancelUrl = '',
        public readonly ?Customer $customer = null,
        public readonly array $items = [],
        public readonly array $metadata = [],
        public readonly ?string $cardNumber = null,
        public readonly ?string $cardExpMonth = null,
        public readonly ?string $cardExpYear = null,
        public readonly ?string $cardCvv = null,
        public readonly ?string $cardHolder = null,
        public readonly ?string $recurringToken = null,
    ) {
    }

    /**
     * Create from an associative array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $customer = null;
        if (isset($data['customer']) && is_array($data['customer'])) {
            $customer = Customer::fromArray($data['customer']);
        }

        return new self(
            amount:         (float) ($data['amount'] ?? 0),
            currency:       (string) ($data['currency'] ?? 'SAR'),
            orderId:        (string) ($data['order_id'] ?? $data['orderId'] ?? ''),
            description:    (string) ($data['description'] ?? ''),
            callbackUrl:    (string) ($data['callback_url'] ?? $data['callbackUrl'] ?? ''),
            returnUrl:      (string) ($data['return_url'] ?? $data['returnUrl'] ?? ''),
            cancelUrl:      (string) ($data['cancel_url'] ?? $data['cancelUrl'] ?? ''),
            customer:       $customer,
            items:          (array) ($data['items'] ?? []),
            metadata:       (array) ($data['metadata'] ?? []),
            cardNumber:     isset($data['card_number']) ? (string) $data['card_number'] : null,
            cardExpMonth:   isset($data['card_exp_month']) ? (string) $data['card_exp_month'] : null,
            cardExpYear:    isset($data['card_exp_year']) ? (string) $data['card_exp_year'] : null,
            cardCvv:        isset($data['card_cvv']) ? (string) $data['card_cvv'] : null,
            cardHolder:     isset($data['card_holder']) ? (string) $data['card_holder'] : null,
            recurringToken: isset($data['recurring_token']) ? (string) $data['recurring_token'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'amount'          => $this->amount,
            'currency'        => $this->currency,
            'order_id'        => $this->orderId,
            'description'     => $this->description,
            'callback_url'    => $this->callbackUrl,
            'return_url'      => $this->returnUrl,
            'cancel_url'      => $this->cancelUrl,
            'customer'        => $this->customer?->toArray(),
            'items'           => $this->items,
            'metadata'        => $this->metadata,
            'card_number'     => $this->cardNumber,
            'card_exp_month'  => $this->cardExpMonth,
            'card_exp_year'   => $this->cardExpYear,
            'card_cvv'        => $this->cardCvv,
            'card_holder'     => $this->cardHolder,
            'recurring_token' => $this->recurringToken,
        ], fn(mixed $v) => $v !== null && $v !== '' && $v !== []);
    }
}
