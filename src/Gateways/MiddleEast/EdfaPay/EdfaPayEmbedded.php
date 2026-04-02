<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\EdfaPay;

use AzozzALFiras\PaymentGateway\DTOs\PaymentResponse;
use AzozzALFiras\PaymentGateway\DTOs\RefundRequest;
use AzozzALFiras\PaymentGateway\DTOs\RefundResponse;
use AzozzALFiras\PaymentGateway\Http\HttpClient;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * EdfaPay Embedded S2S (Server-to-Server) Integration.
 *
 * Provides full checkout control with direct card payment processing.
 *
 * Supported operations:
 *  - SALE (Direct card payment)
 *  - AUTH (Authorize only)
 *  - CAPTURE (Capture authorized payment)
 *  - STATUS (Transaction status)
 *  - TRANSACTION DETAILS
 *  - FULL REFUND
 *  - PARTIAL REFUND
 *  - CREDIT VOID
 *  - RECURRING
 *  - EXTRA AMOUNT
 *
 * @link https://edfapay-payment-gateway-api.readme.io/reference/hosted-integration-s2s
 */
class EdfaPayEmbedded
{
    private const ENDPOINT = '/payment/post';

    public function __construct(
        private readonly HttpClient $http,
        private readonly string $baseUrl,
        private readonly string $clientKey,
        private readonly string $password,
    ) {
    }

    /**
     * Process a direct card payment (SALE).
     *
     * @param array<string, mixed> $params Card + order details
     * @return PaymentResponse
     */
    public function sale(array $params): PaymentResponse
    {
        $payload = $this->buildPayload('SALE', $params);
        return $this->processPayment($payload);
    }

    /**
     * Authorize a payment (pre-auth, no capture).
     *
     * @param array<string, mixed> $params
     * @return PaymentResponse
     */
    public function authorize(array $params): PaymentResponse
    {
        $payload = $this->buildPayload('AUTH', $params);
        return $this->processPayment($payload);
    }

    /**
     * Capture a previously authorized payment.
     *
     * @param string     $transactionId
     * @param float|null $amount Amount to capture (null = full)
     * @return PaymentResponse
     */
    public function capture(string $transactionId, ?float $amount = null): PaymentResponse
    {
        $payload = [
            'action'     => 'CAPTURE',
            'client_key' => $this->clientKey,
            'trans_id'   => $transactionId,
        ];

        if ($amount !== null) {
            $payload['amount'] = number_format($amount, 2, '.', '');
        }

        return $this->processPayment($payload);
    }

    /**
     * Get the status of a transaction.
     *
     * @param string $transactionId
     * @param string $orderId
     * @return PaymentResponse
     */
    public function status(string $transactionId, string $orderId): PaymentResponse
    {
        $payload = [
            'action'     => 'GET_TRANS_STATUS',
            'client_key' => $this->clientKey,
            'trans_id'   => $transactionId,
            'order_id'   => $orderId,
        ];

        return $this->processPayment($payload);
    }

    /**
     * Get detailed transaction information.
     *
     * @param string $transactionId
     * @param string $orderId
     * @return PaymentResponse
     */
    public function transactionDetails(string $transactionId, string $orderId): PaymentResponse
    {
        $payload = [
            'action'     => 'GET_TRANS_DETAILS',
            'client_key' => $this->clientKey,
            'trans_id'   => $transactionId,
            'order_id'   => $orderId,
        ];

        return $this->processPayment($payload);
    }

    /**
     * Process a full refund.
     */
    public function fullRefund(RefundRequest $request): RefundResponse
    {
        $payload = array_merge([
            'action'     => 'CREDITVOID',
            'client_key' => $this->clientKey,
            'trans_id'   => $request->transactionId,
            'amount'     => number_format($request->amount, 2, '.', ''),
        ], $request->metadata);

        return $this->processRefund($payload, $request);
    }

    /**
     * Process a partial refund.
     */
    public function partialRefund(RefundRequest $request): RefundResponse
    {
        $payload = array_merge([
            'action'     => 'CREDITVOID',
            'client_key' => $this->clientKey,
            'trans_id'   => $request->transactionId,
            'amount'     => number_format($request->amount, 2, '.', ''),
        ], $request->metadata);

        return $this->processRefund($payload, $request);
    }

    /**
     * Void a credit / refund.
     *
     * @param string $transactionId
     * @return PaymentResponse
     */
    public function creditVoid(string $transactionId): PaymentResponse
    {
        $payload = [
            'action'     => 'CREDITVOID',
            'client_key' => $this->clientKey,
            'trans_id'   => $transactionId,
        ];

        return $this->processPayment($payload);
    }

    /**
     * Process a recurring payment using a saved token.
     *
     * @param string               $recurringToken  First transaction ID
     * @param float                $amount
     * @param string               $currency
     * @param string               $orderId
     * @param string               $orderDescription
     * @param array<string, mixed> $extra
     * @return PaymentResponse
     */
    public function recurring(
        string $recurringToken,
        float $amount,
        string $currency,
        string $orderId,
        string $orderDescription,
        array $extra = []
    ): PaymentResponse {
        $payload = array_merge([
            'action'                   => 'RECURRING_SALE',
            'client_key'               => $this->clientKey,
            'order_id'                 => $orderId,
            'order_amount'             => number_format($amount, 2, '.', ''),
            'order_currency'           => $currency,
            'order_description'        => $orderDescription,
            'recurring_first_trans_id' => $recurringToken,
        ], $extra);

        return $this->processPayment($payload);
    }

    /**
     * Charge an extra amount on an existing transaction.
     *
     * @param string               $transactionId
     * @param float                $extraAmount
     * @param array<string, mixed> $extra
     * @return PaymentResponse
     */
    public function extraAmount(string $transactionId, float $extraAmount, array $extra = []): PaymentResponse
    {
        $payload = array_merge([
            'action'       => 'SALE',
            'client_key'   => $this->clientKey,
            'trans_id'     => $transactionId,
            'extra_amount' => number_format($extraAmount, 2, '.', ''),
        ], $extra);

        return $this->processPayment($payload);
    }

    // ──────────────────────────────────────────
    //  Internal Helpers
    // ──────────────────────────────────────────

    /**
     * Build a payment payload with hash authentication.
     *
     * @param string               $action
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function buildPayload(string $action, array $params): array
    {
        $payload = [
            'action'            => $action,
            'client_key'        => $this->clientKey,
            'order_id'          => $params['order_id'] ?? '',
            'order_amount'      => number_format((float) ($params['order_amount'] ?? 0), 2, '.', ''),
            'order_currency'    => $params['order_currency'] ?? 'SAR',
            'order_description' => $params['order_description'] ?? '',
            'card_number'       => $params['card_number'] ?? '',
            'card_exp_month'    => $params['card_exp_month'] ?? '',
            'card_exp_year'     => $params['card_exp_year'] ?? '',
            'card_cvv2'         => $params['card_cvv2'] ?? $params['card_cvv'] ?? '',
            'payer_first_name'  => $params['payer_first_name'] ?? '',
            'payer_last_name'   => $params['payer_last_name'] ?? '',
            'payer_address'     => $params['payer_address'] ?? '',
            'payer_country'     => $params['payer_country'] ?? 'SA',
            'payer_city'        => $params['payer_city'] ?? '',
            'payer_zip'         => $params['payer_zip'] ?? '',
            'payer_email'       => $params['payer_email'] ?? '',
            'payer_phone'       => $params['payer_phone'] ?? '',
            'payer_ip'          => $params['payer_ip'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'term_url_3ds'      => $params['term_url_3ds'] ?? $params['callback_url'] ?? '',
        ];

        // Generate authentication hash
        $cardNumber = $params['card_number'] ?? '';
        $payerEmail = $params['payer_email'] ?? '';

        if ($cardNumber !== '' && $payerEmail !== '') {
            $payload['hash'] = EdfaPayHash::generate($payerEmail, $cardNumber, $this->password);
        }

        // Include recurring flag if requested
        if (! empty($params['recurring_init'])) {
            $payload['recurring_init'] = 'Y';
        }

        // Merge any extra parameters
        foreach ($params as $key => $value) {
            if (! isset($payload[$key])) {
                $payload[$key] = $value;
            }
        }

        return $payload;
    }

    /**
     * Execute a payment request and parse the response.
     *
     * @param array<string, mixed> $payload
     * @return PaymentResponse
     */
    private function processPayment(array $payload): PaymentResponse
    {
        $response = $this->http->postForm($this->baseUrl . self::ENDPOINT, $payload);

        $result = (string) Arr::get($response, 'result', '');
        $isSuccess = strtoupper($result) === 'SUCCESS';
        $redirectUrl = (string) Arr::get($response, 'redirect_url', '');

        return new PaymentResponse(
            success:        $isSuccess || ! empty($redirectUrl),
            transactionId:  (string) Arr::get($response, 'trans_id', ''),
            status:         (string) Arr::get($response, 'status', $result),
            message:        (string) Arr::get($response, 'decline_reason', Arr::get($response, 'descriptor', '')),
            amount:         (float) Arr::get($response, 'order_amount', 0),
            currency:       (string) Arr::get($response, 'order_currency', ''),
            redirectUrl:    $redirectUrl,
            recurringToken: Arr::get($response, 'recurring_token') ? (string) $response['recurring_token'] : null,
            rawResponse:    $response,
        );
    }

    /**
     * Execute a refund request and parse the response.
     *
     * @param array<string, mixed> $payload
     * @param RefundRequest        $request
     * @return RefundResponse
     */
    private function processRefund(array $payload, RefundRequest $request): RefundResponse
    {
        $response = $this->http->postForm($this->baseUrl . self::ENDPOINT, $payload);
        $result = (string) Arr::get($response, 'result', '');

        return new RefundResponse(
            success:       strtoupper($result) === 'SUCCESS',
            refundId:      (string) Arr::get($response, 'trans_id', ''),
            transactionId: $request->transactionId,
            status:        $result,
            message:       (string) Arr::get($response, 'decline_reason', ''),
            amount:        $request->amount,
            currency:      $request->currency,
            rawResponse:   $response,
        );
    }
}
