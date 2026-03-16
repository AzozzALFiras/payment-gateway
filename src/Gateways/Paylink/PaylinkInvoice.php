<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\Paylink;

use AzozzALFiras\PaymentGateway\DTOs\InvoiceRequest;
use AzozzALFiras\PaymentGateway\DTOs\InvoiceResponse;
use AzozzALFiras\PaymentGateway\Http\HttpClient;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * Paylink Invoice Management.
 *
 * Operations:
 *  - Create Invoice (addInvoice)
 *  - Get Invoice (getInvoice)
 *  - Cancel Invoice (cancelInvoice)
 *  - Send Digital Product Info
 *
 * @link https://developer.paylink.sa/docs/add-invoice
 */
class PaylinkInvoice
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly PaylinkAuth $auth,
        private readonly string $baseUrl,
    ) {
    }

    /**
     * Create a new invoice.
     *
     * @link https://developer.paylink.sa/reference/addgatewayinvoiceusingpost
     */
    public function create(InvoiceRequest $request): InvoiceResponse
    {
        $payload = [
            'amount'      => $request->amount,
            'orderNumber' => $request->orderId ?: ('ORD-' . uniqid()),
            'callBackUrl' => $request->callbackUrl,
            'note'        => $request->description,
        ];

        if ($request->customer !== null) {
            $payload['clientName']    = $request->customer->name;
            $payload['clientEmail']   = $request->customer->email;
            $payload['clientMobile']  = $request->customer->phone;
        }

        if (! empty($request->items)) {
            $payload['products'] = array_map(fn(array $item) => [
                'title'       => $item['name'] ?? '',
                'price'       => (float) ($item['price'] ?? 0),
                'qty'         => (int) ($item['quantity'] ?? 1),
                'description' => $item['description'] ?? '',
                'isDigital'   => $item['is_digital'] ?? false,
            ], $request->items);
        }

        if ($request->currency !== 'SAR') {
            $payload['currency'] = $request->currency;
        }

        if ($request->smsNotification !== null) {
            $payload['smsMessage'] = $request->smsNotification;
        }

        $payload = array_merge($payload, $request->metadata);

        $response = $this->http->post(
            $this->baseUrl . '/api/addInvoice',
            $payload,
            ['Authorization' => 'Bearer ' . $this->auth->getToken()]
        );

        $transactionNo = (string) Arr::get($response, 'transactionNo', '');

        return new InvoiceResponse(
            success:       ! empty($transactionNo),
            invoiceId:     (string) Arr::get($response, 'orderNumber', ''),
            transactionNo: $transactionNo,
            status:        (string) Arr::get($response, 'orderStatus', 'created'),
            message:       ! empty($transactionNo) ? 'Invoice created successfully' : 'Invoice creation failed',
            amount:        (float) Arr::get($response, 'amount', $request->amount),
            currency:      $request->currency,
            paymentUrl:    (string) Arr::get($response, 'url', ''),
            rawResponse:   $response,
        );
    }

    /**
     * Get invoice details by transaction number.
     *
     * @link https://developer.paylink.sa/reference/getgatewayinvoiceusingget
     */
    public function get(string $transactionNo): InvoiceResponse
    {
        $response = $this->http->get(
            $this->baseUrl . "/api/getInvoice/{$transactionNo}",
            ['Authorization' => 'Bearer ' . $this->auth->getToken()]
        );

        return new InvoiceResponse(
            success:       true,
            invoiceId:     (string) Arr::get($response, 'orderNumber', ''),
            transactionNo: (string) Arr::get($response, 'transactionNo', $transactionNo),
            status:        (string) Arr::get($response, 'orderStatus', ''),
            message:       'Invoice retrieved successfully',
            amount:        (float) Arr::get($response, 'amount', 0),
            currency:      (string) Arr::get($response, 'currency', 'SAR'),
            paymentUrl:    (string) Arr::get($response, 'url', ''),
            rawResponse:   $response,
        );
    }

    /**
     * Cancel an invoice.
     *
     * @param string $transactionNo
     * @return InvoiceResponse
     *
     * @link https://developer.paylink.sa/reference/cancelgatewayinvoiceusingpost-1
     */
    public function cancel(string $transactionNo): InvoiceResponse
    {
        $response = $this->http->post(
            $this->baseUrl . '/api/cancelInvoice',
            ['transactionNo' => $transactionNo],
            ['Authorization' => 'Bearer ' . $this->auth->getToken()]
        );

        return new InvoiceResponse(
            success:       true,
            invoiceId:     '',
            transactionNo: $transactionNo,
            status:        'cancelled',
            message:       (string) Arr::get($response, 'message', 'Invoice cancelled'),
            rawResponse:   $response,
        );
    }

    /**
     * Send digital product information to the payer.
     *
     * @param string $transactionNo
     * @param string $productInfo   The digital product details/link
     * @return array<string, mixed>
     *
     * @link https://developer.paylink.sa/reference/sendproductinfotopayerusingpost
     */
    public function sendDigitalProduct(string $transactionNo, string $productInfo): array
    {
        return $this->http->post(
            $this->baseUrl . '/api/sendProductInfoToPayerUsingPost',
            [
                'transactionNo' => $transactionNo,
                'productInfo'   => $productInfo,
            ],
            ['Authorization' => 'Bearer ' . $this->auth->getToken()]
        );
    }
}
