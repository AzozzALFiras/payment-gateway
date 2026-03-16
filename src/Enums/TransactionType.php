<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Enums;

/**
 * Transaction Types supported across payment gateways.
 */
enum TransactionType: string
{
    case SALE      = 'sale';
    case AUTH      = 'auth';
    case CAPTURE   = 'capture';
    case VOID      = 'void';
    case REFUND    = 'refund';
    case RECURRING = 'recurring';
    case CHECKOUT  = 'checkout';
    case INVOICE   = 'invoice';

    /**
     * Human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::SALE      => 'Sale (Direct Charge)',
            self::AUTH      => 'Authorization (Hold)',
            self::CAPTURE   => 'Capture',
            self::VOID      => 'Void',
            self::REFUND    => 'Refund',
            self::RECURRING => 'Recurring Payment',
            self::CHECKOUT  => 'Checkout (Redirect)',
            self::INVOICE   => 'Invoice',
        };
    }

    /**
     * Whether this type results in money movement.
     */
    public function chargesFunds(): bool
    {
        return in_array($this, [
            self::SALE,
            self::CAPTURE,
            self::RECURRING,
        ], true);
    }
}
