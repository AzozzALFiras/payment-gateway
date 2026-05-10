<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\MiddleEast\MyFatoorah;

use AzozzALFiras\PaymentGateway\Http\HttpClient;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * MyFatoorah Session Management.
 *
 * Embedded checkout sessions are not yet wired to MyFatoorah's v2
 * InitiateSession endpoint. The previous v3 implementation was non-functional
 * (the v3 Orders API rejects the payload shape). Both methods throw until
 * a v2-compatible implementation lands.
 */
class MyFatoorahSession
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly MyFatoorahGateway $gateway,
    ) {
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    public function create(
        float $amount,
        string $mode = 'COMPLETE_PAYMENT',
        string $currency = 'KWD',
        array $extra = []
    ): array {
        throw new \BadMethodCallException(
            'MyFatoorah embedded sessions are not implemented in this release. ' .
            'Use purchase() or createInvoice() for the hosted checkout flow.'
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getDetails(string $sessionId): array
    {
        throw new \BadMethodCallException(
            'MyFatoorah embedded sessions are not implemented in this release. ' .
            'Use status() or getInvoice() instead.'
        );
    }
}
