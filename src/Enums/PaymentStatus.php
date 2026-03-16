<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Enums;

/**
 * Unified Payment Status across all gateways.
 *
 * Each gateway uses different status strings — this enum normalizes them
 * into a consistent set of statuses for the consuming application.
 */
enum PaymentStatus: string
{
    case PENDING     = 'pending';
    case INITIATED   = 'initiated';
    case AUTHORIZED  = 'authorized';
    case CAPTURED    = 'captured';
    case PAID        = 'paid';
    case FAILED      = 'failed';
    case CANCELLED   = 'cancelled';
    case REFUNDED    = 'refunded';
    case PARTIAL_REFUND = 'partial_refund';
    case EXPIRED     = 'expired';
    case VOIDED      = 'voided';
    case DECLINED    = 'declined';

    /**
     * Whether this status represents a successful payment.
     */
    public function isSuccessful(): bool
    {
        return in_array($this, [
            self::CAPTURED,
            self::PAID,
            self::AUTHORIZED,
        ], true);
    }

    /**
     * Whether this status is a final state (no further changes expected).
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::PAID,
            self::CAPTURED,
            self::FAILED,
            self::CANCELLED,
            self::REFUNDED,
            self::EXPIRED,
            self::VOIDED,
            self::DECLINED,
        ], true);
    }

    /**
     * Whether a refund is possible from this status.
     */
    public function isRefundable(): bool
    {
        return in_array($this, [
            self::CAPTURED,
            self::PAID,
        ], true);
    }

    /**
     * Normalize a raw gateway-specific status string.
     *
     * Maps vendor-specific status strings to unified PaymentStatus values.
     */
    public static function normalize(string $rawStatus): self
    {
        $status = strtoupper(trim($rawStatus));

        return match (true) {
            // Successful statuses
            in_array($status, ['CAPTURED', 'COMPLETED', 'A', 'SUCCESS'], true)
                => self::CAPTURED,
            in_array($status, ['PAID', 'APPROVED'], true)
                => self::PAID,
            in_array($status, ['AUTHORIZED', 'AUTH'], true)
                => self::AUTHORIZED,

            // Failed statuses
            in_array($status, ['FAILED', 'FAILURE', 'ERROR', 'D', 'DECLINED'], true)
                => self::FAILED,
            in_array($status, ['DECLINED', 'REJECTED'], true)
                => self::DECLINED,

            // Transitional statuses
            in_array($status, ['PENDING', 'PROCESSING', 'IN_PROGRESS'], true)
                => self::PENDING,
            in_array($status, ['INITIATED', 'CREATED', 'NEW'], true)
                => self::INITIATED,

            // Reversal statuses
            in_array($status, ['REFUNDED', 'FULL_REFUND'], true)
                => self::REFUNDED,
            in_array($status, ['PARTIAL_REFUND', 'PARTIALLY_REFUNDED'], true)
                => self::PARTIAL_REFUND,
            in_array($status, ['CANCELLED', 'CANCELED', 'CANCEL'], true)
                => self::CANCELLED,
            in_array($status, ['VOIDED', 'VOID'], true)
                => self::VOIDED,
            in_array($status, ['EXPIRED'], true)
                => self::EXPIRED,

            default => self::PENDING,
        };
    }
}
