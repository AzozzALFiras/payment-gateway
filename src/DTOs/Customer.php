<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\DTOs;

/**
 * Represents a customer in a payment transaction.
 */
final class Customer
{
    public function __construct(
        public readonly string $name = '',
        public readonly string $email = '',
        public readonly string $phone = '',
        public readonly string $address = '',
        public readonly string $city = '',
        public readonly string $state = '',
        public readonly string $country = '',
        public readonly string $zip = '',
        public readonly string $ip = '',
    ) {
    }

    /**
     * Create from an associative array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name:    (string) ($data['name'] ?? ''),
            email:   (string) ($data['email'] ?? ''),
            phone:   (string) ($data['phone'] ?? ''),
            address: (string) ($data['address'] ?? ''),
            city:    (string) ($data['city'] ?? ''),
            state:   (string) ($data['state'] ?? ''),
            country: (string) ($data['country'] ?? ''),
            zip:     (string) ($data['zip'] ?? ''),
            ip:      (string) ($data['ip'] ?? ''),
        );
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return array_filter([
            'name'    => $this->name,
            'email'   => $this->email,
            'phone'   => $this->phone,
            'address' => $this->address,
            'city'    => $this->city,
            'state'   => $this->state,
            'country' => $this->country,
            'zip'     => $this->zip,
            'ip'      => $this->ip,
        ], fn(string $v) => $v !== '');
    }
}
