<?php

/**
 * MyFatoorah Payment Example
 *
 * Demonstrates the full payment lifecycle:
 *  1. Create a payment
 *  2. Redirect customer
 *  3. Check payment status
 */

require_once __DIR__ . '/../vendor/autoload.php';

use AzozzALFiras\PaymentGateway\PaymentGateway;
use AzozzALFiras\PaymentGateway\DTOs\PaymentRequest;
use AzozzALFiras\PaymentGateway\DTOs\Customer;

// ─── 1. Create the gateway ──────────────────────────────────

$gateway = PaymentGateway::myfatoorah([
    'api_key'  => 'YOUR_API_KEY_HERE',
    'testMode' => true,  // Use demo environment
]);

echo "Gateway: {$gateway->getName()}\n";
echo "Mode: " . ($gateway->isTestMode() ? 'Test' : 'Live') . "\n\n";

// ─── 2. Create a payment ────────────────────────────────────

$request = new PaymentRequest(
    amount:      100.00,
    currency:    'KWD',
    orderId:     'ORDER-001',
    description: 'Premium Subscription',
    callbackUrl: 'https://yoursite.com/payment/callback',
    returnUrl:   'https://yoursite.com/payment/success',
    customer:    new Customer(
        name:    'Ahmed Ali',
        email:   'ahmed@example.com',
        phone:   '96512345678',
        country: 'KWT',
    ),
    items: [
        ['name' => 'Premium Plan', 'quantity' => 1, 'price' => 100.00],
    ],
);

try {
    $response = $gateway->purchase($request);

    if ($response->isSuccessful()) {
        echo "✅ Payment created successfully!\n";
        echo "Transaction ID: {$response->transactionId}\n";
        echo "Payment URL: {$response->paymentUrl}\n";

        // Redirect customer: header("Location: {$response->paymentUrl}");
    } else {
        echo "❌ Payment failed: {$response->message}\n";
    }
} catch (\Exception $e) {
    echo "Error: {$e->getMessage()}\n";
}

// ─── 3. Check payment status ────────────────────────────────

try {
    $status = $gateway->status('PAYMENT_ID_HERE');
    echo "\nPayment Status: {$status->status}\n";
    echo "Amount: {$status->amount} {$status->currency}\n";
} catch (\Exception $e) {
    echo "Error: {$e->getMessage()}\n";
}

// ─── 4. Session-based payment (Embedded) ────────────────────

try {
    $session = $gateway->sessions()->create(
        amount:   50.00,
        mode:     'COMPLETE_PAYMENT',
        currency: 'KWD',
    );

    echo "\nSession ID: " . ($session['SessionId'] ?? 'N/A') . "\n";
    echo "Session Expiry: " . ($session['SessionExpiry'] ?? 'N/A') . "\n";
} catch (\Exception $e) {
    echo "Error: {$e->getMessage()}\n";
}
