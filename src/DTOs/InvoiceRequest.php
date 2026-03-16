<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\DTOs;

/**
 * Invoice request DTO.
 */
final class InvoiceRequest
{
    /**
     * @param array<int, array<string, mixed>> $items    Products / line items
     * @param array<string, mixed>             $metadata Extra gateway-specific params
     */
    public function __construct(
        public readonly float $amount,
        public readonly string $currency = 'SAR',
        public readonly string $orderId = '',
        public readonly string $description = '',
        public readonly string $callbackUrl = '',
        public readonly ?Customer $customer = null,
        public readonly array $items = [],
        public readonly array $metadata = [],
        public readonly ?string $smsNotification = null,
        public readonly ?string $emailNotification = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $customer = null;
        if (isset($data['customer']) && is_array($data['customer'])) {
            $customer = Customer::fromArray($data['customer']);
        }

        return new self(
            amount:            (float) ($data['amount'] ?? 0),
            currency:          (string) ($data['currency'] ?? 'SAR'),
            orderId:           (string) ($data['order_id'] ?? ''),
            description:       (string) ($data['description'] ?? ''),
            callbackUrl:       (string) ($data['callback_url'] ?? ''),
            customer:          $customer,
            items:             (array) ($data['items'] ?? []),
            metadata:          (array) ($data['metadata'] ?? []),
            smsNotification:   isset($data['sms_notification']) ? (string) $data['sms_notification'] : null,
            emailNotification: isset($data['email_notification']) ? (string) $data['email_notification'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'amount'             => $this->amount,
            'currency'           => $this->currency,
            'order_id'           => $this->orderId,
            'description'        => $this->description,
            'callback_url'       => $this->callbackUrl,
            'customer'           => $this->customer?->toArray(),
            'items'              => $this->items,
            'metadata'           => $this->metadata,
            'sms_notification'   => $this->smsNotification,
            'email_notification' => $this->emailNotification,
        ], fn(mixed $v) => $v !== null && $v !== '' && $v !== []);
    }
}
