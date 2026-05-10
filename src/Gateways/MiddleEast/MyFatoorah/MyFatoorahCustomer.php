<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\MiddleEast\MyFatoorah;

use AzozzALFiras\PaymentGateway\Http\HttpClient;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * MyFatoorah Customer Operations.
 *
 * The v2 API has no standalone customer-details endpoint. The previous v3
 * implementation was non-functional. getDetails() throws until/unless a
 * supported lookup is wired (saved-card flows or vault APIs).
 */
class MyFatoorahCustomer
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly MyFatoorahGateway $gateway,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getDetails(string $customerRef): array
    {
        throw new \BadMethodCallException(
            'MyFatoorah v2 has no standalone customer lookup endpoint. ' .
            'Customer info is returned as part of GetPaymentStatus; use status() or getInvoice() and read it from the raw response.'
        );
    }
}
