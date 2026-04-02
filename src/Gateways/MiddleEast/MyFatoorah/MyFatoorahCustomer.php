<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\MyFatoorah;

use AzozzALFiras\PaymentGateway\Http\HttpClient;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * MyFatoorah Customer Operations (V3 API).
 *
 * @link https://docs.myfatoorah.com/reference/get-customer-details
 */
class MyFatoorahCustomer
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly MyFatoorahGateway $gateway,
    ) {
    }

    /**
     * Get customer details by reference.
     *
     * @param string $customerRef
     * @return array<string, mixed>
     */
    public function getDetails(string $customerRef): array
    {
        $response = $this->http->get(
            $this->gateway->getBaseUrl() . "/v3/customers/{$customerRef}"
        );

        return (array) Arr::get($response, 'Data', $response);
    }
}
