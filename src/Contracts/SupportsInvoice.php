<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Contracts;

use AzozzALFiras\PaymentGateway\DTOs\InvoiceRequest;
use AzozzALFiras\PaymentGateway\DTOs\InvoiceResponse;

/**
 * Gateways implementing this interface support invoice management.
 */
interface SupportsInvoice
{
    /**
     * Create a new invoice.
     */
    public function createInvoice(InvoiceRequest $request): InvoiceResponse;

    /**
     * Get invoice details by its identifier.
     */
    public function getInvoice(string $invoiceId): InvoiceResponse;
}
