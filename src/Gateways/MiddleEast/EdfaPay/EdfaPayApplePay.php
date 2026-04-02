<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\MiddleEast\EdfaPay;

use AzozzALFiras\PaymentGateway\DTOs\PaymentResponse;
use AzozzALFiras\PaymentGateway\Http\HttpClient;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * EdfaPay Apple Pay Integration.
 *
 * Supports embedded Apple Pay SALE operations.
 *
 * @link https://edfapay-payment-gateway-api.readme.io/reference/embedded-apple-pay-sale-api
 */
class EdfaPayApplePay
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly string $baseUrl,
        private readonly string $clientKey,
    ) {
    }

    /**
     * Process an Apple Pay payment.
     *
     * @param float                $amount
     * @param string               $currency
     * @param string               $orderId
     * @param string               $orderDescription
     * @param string               $applePayToken    Base64 encoded Apple Pay payment token
     * @param string               $payerEmail
     * @param array<string, mixed> $extra
     * @return PaymentResponse
     */
    public function sale(
        float $amount,
        string $currency,
        string $orderId,
        string $orderDescription,
        string $applePayToken,
        string $payerEmail,
        array $extra = []
    ): PaymentResponse {
        $payload = array_merge([
            'action'            => 'APPLEPAY',
            'client_key'        => $this->clientKey,
            'order_id'          => $orderId,
            'order_amount'      => number_format($amount, 2, '.', ''),
            'order_currency'    => $currency,
            'order_description' => $orderDescription,
            'apple_pay_token'   => $applePayToken,
            'payer_email'       => $payerEmail,
        ], $extra);

        $response = $this->http->postForm($this->baseUrl . '/payment/post', $payload);

        $result = (string) Arr::get($response, 'result', '');

        return new PaymentResponse(
            success:       strtoupper($result) === 'SUCCESS',
            transactionId: (string) Arr::get($response, 'trans_id', ''),
            status:        $result,
            message:       (string) Arr::get($response, 'decline_reason', ''),
            amount:        $amount,
            currency:      $currency,
            rawResponse:   $response,
        );
    }
}
