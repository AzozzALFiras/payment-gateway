<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\MiddleEast\MyFatoorah;

use AzozzALFiras\PaymentGateway\Http\HttpClient;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * MyFatoorah Session Management (V3 API).
 *
 * Sessions are used for Embedded Payment flows:
 *  - COMPLETE_PAYMENT: Payment is completed within the session
 *  - COLLECT_DETAILS:  Card details are collected for later processing
 *
 * @link https://docs.myfatoorah.com/reference/create-session
 */
class MyFatoorahSession
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly MyFatoorahGateway $gateway,
    ) {
    }

    /**
     * Create a new payment session.
     *
     * @param float                $amount
     * @param string               $mode     'COMPLETE_PAYMENT' or 'COLLECT_DETAILS'
     * @param string               $currency ISO 4217 currency code
     * @param array<string, mixed> $extra    Additional parameters
     * @return array<string, mixed>
     */
    public function create(
        float $amount,
        string $mode = 'COMPLETE_PAYMENT',
        string $currency = 'KWD',
        array $extra = []
    ): array {
        $payload = array_merge([
            'PaymentMode' => $mode,
            'Order'       => [
                'Amount'   => $amount,
                'Currency' => $currency,
            ],
        ], $extra);

        $response = $this->http->post(
            $this->gateway->getBaseUrl() . '/v3/sessions',
            $payload
        );

        return (array) Arr::get($response, 'Data', $response);
    }

    /**
     * Get session details by SessionId.
     *
     * @param string $sessionId
     * @return array<string, mixed>
     *
     * @link https://docs.myfatoorah.com/reference/get-session-details
     */
    public function getDetails(string $sessionId): array
    {
        $response = $this->http->get(
            $this->gateway->getBaseUrl() . "/v3/sessions/{$sessionId}"
        );

        return (array) Arr::get($response, 'Data', $response);
    }
}
