<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Support;

/**
 * Currency validation and formatting utilities.
 */
final class Currency
{
    /**
     * ISO 4217 codes commonly supported by MENA payment gateways.
     *
     * @var array<string, string>
     */
    private const SUPPORTED = [
        'SAR' => 'Saudi Riyal',
        'AED' => 'UAE Dirham',
        'KWD' => 'Kuwaiti Dinar',
        'BHD' => 'Bahraini Dinar',
        'QAR' => 'Qatari Riyal',
        'OMR' => 'Omani Rial',
        'EGP' => 'Egyptian Pound',
        'JOD' => 'Jordanian Dinar',
        'USD' => 'US Dollar',
        'EUR' => 'Euro',
        'GBP' => 'British Pound',
    ];

    /**
     * Check if a currency code is supported.
     */
    public static function isValid(string $code): bool
    {
        return isset(self::SUPPORTED[strtoupper($code)]);
    }

    /**
     * Get display name for a currency code.
     */
    public static function getName(string $code): string
    {
        return self::SUPPORTED[strtoupper($code)] ?? $code;
    }

    /**
     * Normalize a currency code to uppercase.
     */
    public static function normalize(string $code): string
    {
        return strtoupper(trim($code));
    }

    /**
     * Format an amount with 2 decimal places.
     */
    public static function formatAmount(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
