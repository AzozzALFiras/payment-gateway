<?php

/**
 * Paylink Invoice Example
 *
 * Demonstrates the invoice-based payment flow:
 *  1. Create an invoice
 *  2. Redirect customer to payment page
 *  3. Check payment status
 *  4. Cancel invoice
 *  5. Reconciliation queries
 */

require_once __DIR__ . '/../vendor/autoload.php';

use AzozzALFiras\PaymentGateway\PaymentGateway;
use AzozzALFiras\PaymentGateway\DTOs\InvoiceRequest;
use AzozzALFiras\PaymentGateway\DTOs\Customer;

// ─── 1. Create the gateway ──────────────────────────────────

$gateway = PaymentGateway::paylink([
    'api_id'     => 'YOUR_API_ID',
    'secret_key' => 'YOUR_SECRET_KEY',
    'testMode'   => true,  // Use pilot environment
]);

echo "Gateway: {$gateway->getName()}\n\n";

// ─── 2. Create an invoice ───────────────────────────────────

$invoice = new InvoiceRequest(
    amount:      250.00,
    currency:    'SAR',
    orderId:     'INV-2026-001',
    description: 'Web Development Services',
    callbackUrl: 'https://yoursite.com/paylink/callback',
    customer:    new Customer(
        name:  'Mohammed Salem',
        email: 'mohammed@example.com',
        phone: '966512345678',
    ),
    items: [
        [
            'name'        => 'Website Design',
            'price'       => 200.00,
            'quantity'    => 1,
            'description' => 'Custom website design',
        ],
        [
            'name'        => 'Domain Registration',
            'price'       => 50.00,
            'quantity'    => 1,
            'description' => '.com domain for 1 year',
        ],
    ],
);

try {
    $result = $gateway->createInvoice($invoice);

    if ($result->isSuccessful()) {
        echo "✅ Invoice created!\n";
        echo "Transaction No: {$result->transactionNo}\n";
        echo "Payment URL: {$result->paymentUrl}\n";
        echo "Order ID: {$result->invoiceId}\n";

        // Redirect: header("Location: {$result->paymentUrl}");
    } else {
        echo "❌ Failed: {$result->message}\n";
    }
} catch (\Exception $e) {
    echo "Error: {$e->getMessage()}\n";
}

// ─── 3. Check payment status ────────────────────────────────

try {
    $invoice = $gateway->getInvoice('TRANSACTION_NO_HERE');
    echo "\nInvoice Status: {$invoice->status}\n";
    echo "Amount: {$invoice->amount} {$invoice->currency}\n";
} catch (\Exception $e) {
    echo "Error: {$e->getMessage()}\n";
}

// ─── 4. Cancel invoice ──────────────────────────────────────

try {
    $cancelled = $gateway->cancelInvoice('TRANSACTION_NO_HERE');
    echo "\nCancel Status: {$cancelled->status}\n";
} catch (\Exception $e) {
    echo "Error: {$e->getMessage()}\n";
}

// ─── 5. Reconciliation ─────────────────────────────────────

try {
    // Get transactions by date range
    $transactions = $gateway->reconcile()->getByDateRange('2026-01-01', '2026-03-16');
    echo "\nTransactions found: " . count($transactions) . "\n";

    // Get settlements
    $settlements = $gateway->reconcile()->getSettlements('2026-01-01', '2026-03-16');
    echo "Settlements found: " . count($settlements) . "\n";
} catch (\Exception $e) {
    echo "Error: {$e->getMessage()}\n";
}

// ─── 6. Send digital product ────────────────────────────────

try {
    $gateway->sendDigitalProduct(
        'TRANSACTION_NO_HERE',
        'Download your e-book: https://yoursite.com/download/ebook-123'
    );
    echo "\n✅ Digital product info sent to customer.\n";
} catch (\Exception $e) {
    echo "Error: {$e->getMessage()}\n";
}
