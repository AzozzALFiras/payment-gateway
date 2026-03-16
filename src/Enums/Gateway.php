<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Enums;

/**
 * Supported Payment Gateway Drivers.
 *
 * Usage:
 *   PaymentGateway::create(Gateway::TAP->value, [...]);
 *   Gateway::fromName('tap'); // Gateway::TAP
 *   Gateway::TAP->label();   // 'Tap Payments'
 *   Gateway::TAP->country(); // ['KWT', 'SAU', 'ARE', ...]
 */
enum Gateway: string
{
    case MYFATOORAH = 'myfatoorah';
    case PAYLINK    = 'paylink';
    case EDFAPAY    = 'edfapay';
    case TAP        = 'tap';
    case CLICKPAY   = 'clickpay';
    case TAMARA     = 'tamara';
    case THAWANI    = 'thawani';
    case FATORA     = 'fatora';
    case PAYZATY    = 'payzaty';
    case PAYZAH     = 'payzah';

    /**
     * Human-readable gateway name.
     */
    public function label(): string
    {
        return match ($this) {
            self::MYFATOORAH => 'MyFatoorah',
            self::PAYLINK    => 'Paylink',
            self::EDFAPAY    => 'EdfaPay',
            self::TAP        => 'Tap Payments',
            self::CLICKPAY   => 'ClickPay',
            self::TAMARA     => 'Tamara',
            self::THAWANI    => 'Thawani',
            self::FATORA     => 'Fatora',
            self::PAYZATY    => 'Payzaty',
            self::PAYZAH     => 'Payzah',
        };
    }

    /**
     * Supported ISO 3166-1 alpha-3 country codes.
     *
     * @return array<int, string>
     */
    public function countries(): array
    {
        return match ($this) {
            self::MYFATOORAH => ['KWT', 'SAU', 'ARE', 'BHR', 'QAT', 'OMN', 'EGY', 'JOR'],
            self::PAYLINK    => ['SAU'],
            self::EDFAPAY    => ['SAU', 'ARE', 'BHR', 'QAT', 'OMN', 'EGY', 'JOR'],
            self::TAP        => ['KWT', 'SAU', 'ARE', 'BHR', 'QAT', 'OMN', 'EGY', 'JOR'],
            self::CLICKPAY   => ['SAU', 'ARE', 'EGY', 'OMN', 'JOR'],
            self::TAMARA     => ['SAU', 'ARE', 'KWT', 'BHR', 'QAT'],
            self::THAWANI    => ['OMN'],
            self::FATORA     => ['SAU', 'ARE', 'QAT', 'BHR', 'KWT', 'OMN', 'IRQ', 'JOR', 'EGY'],
            self::PAYZATY    => ['SAU'],
            self::PAYZAH     => ['KWT'],
        };
    }

    /**
     * Default supported currencies.
     *
     * @return array<int, string>
     */
    public function currencies(): array
    {
        return match ($this) {
            self::MYFATOORAH => ['KWD', 'SAR', 'AED', 'BHD', 'QAR', 'OMR', 'EGP', 'JOD'],
            self::PAYLINK    => ['SAR'],
            self::EDFAPAY    => ['SAR', 'AED', 'BHD', 'QAR', 'OMR', 'EGP', 'JOD'],
            self::TAP        => ['KWD', 'SAR', 'AED', 'BHD', 'QAR', 'OMR', 'EGP'],
            self::CLICKPAY   => ['SAR', 'AED', 'EGP', 'OMR', 'JOD'],
            self::TAMARA     => ['SAR', 'AED', 'KWD', 'BHD', 'QAR'],
            self::THAWANI    => ['OMR'],
            self::FATORA     => ['SAR', 'AED', 'QAR', 'BHD', 'KWD', 'OMR', 'IQD', 'JOD', 'EGP'],
            self::PAYZATY    => ['SAR'],
            self::PAYZAH     => ['KWD'],
        };
    }

    /**
     * Whether the gateway supports refunds.
     */
    public function supportsRefund(): bool
    {
        return match ($this) {
            self::MYFATOORAH, self::EDFAPAY, self::TAP, self::CLICKPAY,
            self::TAMARA, self::FATORA => true,
            default => false,
        };
    }

    /**
     * Whether the gateway supports recurring payments.
     */
    public function supportsRecurring(): bool
    {
        return match ($this) {
            self::EDFAPAY, self::FATORA => true,
            default => false,
        };
    }

    /**
     * Whether the gateway is a BNPL (Buy Now Pay Later) provider.
     */
    public function isBNPL(): bool
    {
        return $this === self::TAMARA;
    }

    /**
     * Create from string name (case-insensitive).
     *
     * @throws \ValueError
     */
    public static function fromName(string $name): self
    {
        $name = strtolower(trim($name));

        foreach (self::cases() as $case) {
            if ($case->value === $name) {
                return $case;
            }
        }

        throw new \ValueError("'{$name}' is not a valid gateway name");
    }

    /**
     * Get all gateway values as an array.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
