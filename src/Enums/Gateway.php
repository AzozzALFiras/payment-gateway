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
    case STRIPE     = 'stripe';
    case PAYPAL     = 'paypal';
    case NEONPAY    = 'neonpay';
    case ASIAPAY    = 'asiapay';
    case ZAINCASH   = 'zaincash';
    case MOLLIE     = 'mollie';
    case REDSYS     = 'redsys';
    case GOCARDLESS = 'gocardless';

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
            self::STRIPE     => 'Stripe',
            self::PAYPAL     => 'PayPal',
            self::NEONPAY    => 'NeonPay',
            self::ASIAPAY    => 'AsiaPay',
            self::ZAINCASH   => 'ZainCash',
            self::MOLLIE     => 'Mollie',
            self::REDSYS     => 'Redsys',
            self::GOCARDLESS => 'GoCardless',
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
            self::STRIPE     => ['USA', 'GBR', 'DEU', 'FRA', 'CAN', 'AUS', 'JPN', 'SGP', 'ARE', 'SAU', 'BHR', 'QAT', 'KWT', 'OMN', 'EGY', 'JOR'],
            self::PAYPAL     => ['USA', 'GBR', 'DEU', 'FRA', 'CAN', 'AUS', 'JPN', 'SGP', 'ARE', 'SAU', 'BHR', 'QAT', 'KWT', 'OMN', 'EGY', 'JOR', 'IND', 'BRA', 'MEX'],
            self::NEONPAY    => ['SAU', 'ARE', 'BHR', 'QAT', 'KWT', 'OMN', 'EGY', 'JOR'],
            self::ASIAPAY    => ['IRQ'],
            self::ZAINCASH   => ['IRQ'],
            self::MOLLIE     => ['NLD', 'DEU', 'FRA', 'BEL', 'AUT', 'CHE', 'GBR', 'ESP', 'PRT', 'ITA', 'FIN', 'SWE', 'DNK', 'NOR', 'POL', 'CZE'],
            self::REDSYS     => ['ESP', 'PRT', 'AND'],
            self::GOCARDLESS => ['GBR', 'DEU', 'FRA', 'ESP', 'NLD', 'BEL', 'AUT', 'IRL', 'ITA', 'PRT', 'FIN', 'SWE', 'DNK', 'AUS', 'NZL', 'CAN', 'USA'],
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
            self::STRIPE     => ['USD', 'EUR', 'GBP', 'SAR', 'AED', 'KWD', 'BHD', 'QAR', 'OMR', 'EGP', 'JOD'],
            self::PAYPAL     => ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'SAR', 'AED', 'KWD', 'BHD', 'QAR', 'OMR', 'EGP', 'JOD'],
            self::NEONPAY    => ['SAR', 'AED', 'BHD', 'QAR', 'KWD', 'OMR', 'EGP', 'JOD'],
            self::ASIAPAY    => ['IQD'],
            self::ZAINCASH   => ['IQD'],
            self::MOLLIE     => ['EUR', 'GBP', 'USD', 'CHF', 'SEK', 'NOK', 'DKK', 'PLN', 'CZK', 'HUF', 'RON', 'BGN', 'ISK'],
            self::REDSYS     => ['EUR'],
            self::GOCARDLESS => ['GBP', 'EUR', 'SEK', 'DKK', 'AUD', 'NZD', 'CAD', 'USD'],
        };
    }

    /**
     * Whether the gateway supports refunds.
     */
    public function supportsRefund(): bool
    {
        return match ($this) {
            self::MYFATOORAH, self::EDFAPAY, self::TAP, self::CLICKPAY,
            self::TAMARA, self::FATORA, self::STRIPE, self::PAYPAL, self::NEONPAY,
            self::ASIAPAY, self::MOLLIE, self::REDSYS, self::GOCARDLESS => true,
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
     * Regional classification.
     */
    public function region(): string
    {
        return match ($this) {
            self::STRIPE, self::PAYPAL => 'International',
            self::MYFATOORAH, self::PAYLINK, self::EDFAPAY, self::TAP,
            self::CLICKPAY, self::TAMARA, self::THAWANI, self::FATORA,
            self::PAYZATY, self::PAYZAH, self::NEONPAY,
            self::ASIAPAY, self::ZAINCASH => 'MiddleEast',
            self::MOLLIE, self::REDSYS, self::GOCARDLESS => 'Europe',
        };
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
