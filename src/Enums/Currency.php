<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Enums;

/**
 * MENA Region Currencies with metadata.
 */
enum Currency: string
{
    case KWD = 'KWD';
    case SAR = 'SAR';
    case AED = 'AED';
    case BHD = 'BHD';
    case QAR = 'QAR';
    case OMR = 'OMR';
    case EGP = 'EGP';
    case JOD = 'JOD';
    case IQD = 'IQD';
    case USD = 'USD';
    case EUR = 'EUR';

    /**
     * Currency name.
     */
    public function label(): string
    {
        return match ($this) {
            self::KWD => 'Kuwaiti Dinar',
            self::SAR => 'Saudi Riyal',
            self::AED => 'UAE Dirham',
            self::BHD => 'Bahraini Dinar',
            self::QAR => 'Qatari Riyal',
            self::OMR => 'Omani Rial',
            self::EGP => 'Egyptian Pound',
            self::JOD => 'Jordanian Dinar',
            self::IQD => 'Iraqi Dinar',
            self::USD => 'US Dollar',
            self::EUR => 'Euro',
        };
    }

    /**
     * Number of decimal places used.
     */
    public function decimals(): int
    {
        return match ($this) {
            self::KWD, self::BHD, self::OMR, self::JOD, self::IQD => 3,
            default => 2,
        };
    }

    /**
     * Smallest unit multiplier (e.g., 1 OMR = 1000 baisa).
     */
    public function smallestUnitMultiplier(): int
    {
        return match ($this) {
            self::KWD, self::BHD, self::OMR, self::JOD, self::IQD => 1000,
            default => 100,
        };
    }

    /**
     * Convert major unit amount to smallest unit.
     */
    public function toSmallestUnit(float $amount): int
    {
        return (int) round($amount * $this->smallestUnitMultiplier());
    }

    /**
     * Convert smallest unit amount to major unit.
     */
    public function fromSmallestUnit(int $amount): float
    {
        return $amount / $this->smallestUnitMultiplier();
    }

    /**
     * Format amount with currency code.
     */
    public function format(float $amount): string
    {
        return number_format($amount, $this->decimals()) . ' ' . $this->value;
    }
}
