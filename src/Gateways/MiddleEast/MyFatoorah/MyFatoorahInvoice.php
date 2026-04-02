<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\MyFatoorah;

use AzozzALFiras\PaymentGateway\DTOs\InvoiceRequest;
use AzozzALFiras\PaymentGateway\DTOs\InvoiceResponse;
use AzozzALFiras\PaymentGateway\Http\HttpClient;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * MyFatoorah Invoice Operations (V3 API).
 *
 * @link https://docs.myfatoorah.com/reference/get-invoice-by-invoiceid
 */
class MyFatoorahInvoice
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly MyFatoorahGateway $gateway,
    ) {
    }

    /**
     * Create an invoice via the payment creation endpoint.
     */
    public function create(InvoiceRequest $request): InvoiceResponse
    {
        $payload = [
            'InvoiceValue'       => $request->amount,
            'DisplayCurrencyIso' => $request->currency,
        ];

        if ($request->orderId !== '') {
            $payload['ExternalIdentifier'] = $request->orderId;
        }

        if ($request->callbackUrl !== '') {
            $payload['CallBackUrl'] = $request->callbackUrl;
        }

        if ($request->customer !== null) {
            $payload['CustomerName']   = $request->customer->name;
            $payload['CustomerEmail']  = $request->customer->email;
            $payload['CustomerMobile'] = $request->customer->phone;
        }

        if ($request->smsNotification !== null) {
            $payload['SendInvoiceOption'] = 'SMS';
            $payload['CustomerMobile']    = $request->smsNotification;
        }

        if ($request->emailNotification !== null) {
            $payload['SendInvoiceOption'] = 'Email';
            $payload['CustomerEmail']     = $request->emailNotification;
        }

        if (! empty($request->items)) {
            $payload['InvoiceItems'] = array_map(fn(array $item) => [
                'ItemName'  => $item['name'] ?? '',
                'Quantity'  => $item['quantity'] ?? 1,
                'UnitPrice' => $item['price'] ?? 0,
            ], $request->items);
        }

        $payload = array_merge($payload, $request->metadata);

        $response = $this->http->post(
            $this->gateway->getBaseUrl() . '/v3/payments',
            $payload
        );

        $isSuccess = (bool) Arr::get($response, 'IsSuccess', false);
        $data = (array) Arr::get($response, 'Data', []);

        return new InvoiceResponse(
            success:       $isSuccess,
            invoiceId:     (string) Arr::get($data, 'InvoiceId', ''),
            transactionNo: (string) Arr::get($data, 'PaymentId', ''),
            status:        $isSuccess ? 'created' : 'failed',
            message:       (string) Arr::get($response, 'Message', ''),
            amount:        $request->amount,
            currency:      $request->currency,
            paymentUrl:    (string) Arr::get($data, 'PaymentURL', ''),
            rawResponse:   $response,
        );
    }

    /**
     * Get invoice by InvoiceId.
     *
     * @link https://docs.myfatoorah.com/reference/get-invoice-by-invoiceid
     */
    public function getByInvoiceId(string $invoiceId): InvoiceResponse
    {
        $response = $this->http->get(
            $this->gateway->getBaseUrl() . "/v3/invoices/{$invoiceId}"
        );

        return $this->parseInvoiceResponse($response);
    }

    /**
     * Get invoice by ExternalIdentifier.
     *
     * @link https://docs.myfatoorah.com/reference/get-invoice-by-externalidentifier
     */
    public function getByExternalId(string $externalId): InvoiceResponse
    {
        $response = $this->http->get(
            $this->gateway->getBaseUrl() . "/v3/invoices/external/{$externalId}"
        );

        return $this->parseInvoiceResponse($response);
    }

    /**
     * Parse invoice API response into InvoiceResponse DTO.
     *
     * @param array<string, mixed> $response
     */
    private function parseInvoiceResponse(array $response): InvoiceResponse
    {
        $isSuccess = (bool) Arr::get($response, 'IsSuccess', false);
        $data = (array) Arr::get($response, 'Data', []);

        return new InvoiceResponse(
            success:       $isSuccess,
            invoiceId:     (string) Arr::get($data, 'InvoiceId', ''),
            transactionNo: (string) Arr::get($data, 'PaymentId', ''),
            status:        (string) Arr::get($data, 'InvoiceStatus', ''),
            message:       (string) Arr::get($response, 'Message', ''),
            amount:        (float) Arr::get($data, 'InvoiceValue', 0),
            currency:      (string) Arr::get($data, 'DisplayCurrencyIso', ''),
            paymentUrl:    (string) Arr::get($data, 'PaymentURL', ''),
            rawResponse:   $response,
        );
    }
}
