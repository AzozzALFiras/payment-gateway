<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\MiddleEast\Paylink;

use AzozzALFiras\PaymentGateway\Http\HttpClient;

/**
 * Paylink Reconcile API — transaction and settlement queries.
 *
 * @link https://developer.paylink.sa/reference/getordersbynumberusingget
 */
class PaylinkReconcile
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly PaylinkAuth $auth,
        private readonly string $baseUrl,
    ) {
    }

    /**
     * Get paid transactions by order number.
     *
     * @param string $orderNumber
     * @return array<string, mixed>
     */
    public function getByOrderNumber(string $orderNumber): array
    {
        return $this->http->get(
            $this->baseUrl . "/api/getOrders/byNumber?orderNumber={$orderNumber}",
            ['Authorization' => 'Bearer ' . $this->auth->getToken()]
        );
    }

    /**
     * Get paid transactions by date range.
     *
     * @param string $startDate Format: YYYY-MM-DD
     * @param string $endDate   Format: YYYY-MM-DD
     * @return array<string, mixed>
     */
    public function getByDateRange(string $startDate, string $endDate): array
    {
        return $this->http->get(
            $this->baseUrl . "/api/getOrders/byDateRange?startDate={$startDate}&endDate={$endDate}",
            ['Authorization' => 'Bearer ' . $this->auth->getToken()]
        );
    }

    /**
     * Get paid transactions by settlement.
     *
     * @param string $settlementNo
     * @return array<string, mixed>
     */
    public function getBySettlement(string $settlementNo): array
    {
        return $this->http->get(
            $this->baseUrl . "/api/getOrders/bySettlement?settlementNo={$settlementNo}",
            ['Authorization' => 'Bearer ' . $this->auth->getToken()]
        );
    }

    /**
     * Get all transactions by order number.
     *
     * @param string $orderNumber
     * @return array<string, mixed>
     */
    public function getTransactions(string $orderNumber): array
    {
        return $this->http->get(
            $this->baseUrl . "/api/getTransactions/{$orderNumber}",
            ['Authorization' => 'Bearer ' . $this->auth->getToken()]
        );
    }

    /**
     * Get all settlements by date range.
     *
     * @param string $startDate Format: YYYY-MM-DD
     * @param string $endDate   Format: YYYY-MM-DD
     * @return array<string, mixed>
     */
    public function getSettlements(string $startDate, string $endDate): array
    {
        return $this->http->get(
            $this->baseUrl . "/api/getSettlement/byDateRange?startDate={$startDate}&endDate={$endDate}",
            ['Authorization' => 'Bearer ' . $this->auth->getToken()]
        );
    }
}
