<?php

/**
 * EdfaPay Checkout & Embedded Examples
 *
 * Demonstrates:
 *  1. Hosted checkout flow
 *  2. Embedded S2S card payment
 *  3. Authorize → Capture flow
 *  4. Refund operations
 *  5. Recurring payments
 *  6. Apple Pay integration
 */

require_once __DIR__ . '/../vendor/autoload.php';

use AzozzALFiras\PaymentGateway\PaymentGateway;
use AzozzALFiras\PaymentGateway\DTOs\PaymentRequest;
use AzozzALFiras\PaymentGateway\DTOs\RefundRequest;
use AzozzALFiras\PaymentGateway\DTOs\Customer;

// ─── 1. Create the gateway ──────────────────────────────────

$gateway = PaymentGateway::edfapay([
    'client_key' => 'YOUR_CLIENT_KEY',
    'password'   => 'YOUR_SECRET_PASSWORD',
    'testMode'   => true,  // Use sandbox
]);

echo "Gateway: {$gateway->getName()}\n\n";

// ─── 2. Hosted Checkout Payment ─────────────────────────────

try {
    $request = new PaymentRequest(
        amount:      150.00,
        currency:    'SAR',
        orderId:     'ORD-2026-001',
        description: 'Online Order #001',
        callbackUrl: 'https://yoursite.com/edfapay/callback',
        returnUrl:   'https://yoursite.com/edfapay/success',
        cancelUrl:   'https://yoursite.com/edfapay/cancel',
        customer:    new Customer(
            name:  'Khalid Omar',
            email: 'khalid@example.com',
            phone: '966555123456',
        ),
    );

    $response = $gateway->purchase($request);

    if ($response->requiresRedirect()) {
        echo "✅ Checkout created!\n";
        echo "Redirect URL: {$response->getRedirectUrl()}\n";
        // header("Location: {$response->getRedirectUrl()}");
    }
} catch (\Exception $e) {
    echo "Error: {$e->getMessage()}\n";
}

// ─── 3. Embedded S2S Card Payment ───────────────────────────

try {
    $result = $gateway->embedded()->sale([
        'order_id'          => 'ORD-2026-002',
        'order_amount'      => 75.50,
        'order_currency'    => 'SAR',
        'order_description' => 'Digital Service',
        'card_number'       => '5123456789012346',
        'card_exp_month'    => '12',
        'card_exp_year'     => '2030',
        'card_cvv2'         => '123',
        'payer_first_name'  => 'Khalid',
        'payer_last_name'   => 'Omar',
        'payer_email'       => 'khalid@example.com',
        'payer_phone'       => '966555123456',
        'payer_address'     => 'Riyadh',
        'payer_city'        => 'Riyadh',
        'payer_country'     => 'SA',
        'payer_zip'         => '12345',
        'payer_ip'          => '127.0.0.1',
        'term_url_3ds'      => 'https://yoursite.com/3ds/callback',
        'recurring_init'    => true,  // Enable recurring for this card
    ]);

    if ($result->isSuccessful()) {
        echo "\n✅ S2S Payment successful!\n";
        echo "Transaction ID: {$result->transactionId}\n";
        echo "Recurring Token: {$result->recurringToken}\n";
    } elseif ($result->requiresRedirect()) {
        echo "\n🔐 3DS authentication required.\n";
        echo "Redirect to: {$result->getRedirectUrl()}\n";
    } else {
        echo "\n❌ Payment failed: {$result->message}\n";
    }
} catch (\Exception $e) {
    echo "Error: {$e->getMessage()}\n";
}

// ─── 4. Authorize → Capture Flow ────────────────────────────

try {
    // Step 1: Authorize (hold funds)
    $authResult = $gateway->embedded()->authorize([
        'order_id'          => 'ORD-2026-003',
        'order_amount'      => 200.00,
        'order_currency'    => 'SAR',
        'order_description' => 'Pre-authorized payment',
        'card_number'       => '5123456789012346',
        'card_exp_month'    => '12',
        'card_exp_year'     => '2030',
        'card_cvv2'         => '123',
        'payer_first_name'  => 'Sara',
        'payer_last_name'   => 'Ali',
        'payer_email'       => 'sara@example.com',
        'payer_phone'       => '966555789012',
        'payer_address'     => 'Jeddah',
        'payer_city'        => 'Jeddah',
        'payer_country'     => 'SA',
        'payer_zip'         => '23456',
        'payer_ip'          => '127.0.0.1',
        'term_url_3ds'      => 'https://yoursite.com/3ds/callback',
    ]);

    echo "\nAuth Result: {$authResult->status}\n";

    if ($authResult->isSuccessful()) {
        // Step 2: Capture (charge the authorized amount)
        $captureResult = $gateway->embedded()->capture(
            $authResult->transactionId,
            200.00
        );

        echo "Capture Result: {$captureResult->status}\n";
    }
} catch (\Exception $e) {
    echo "Error: {$e->getMessage()}\n";
}

// ─── 5. Refund ──────────────────────────────────────────────

try {
    $refund = $gateway->refund(new RefundRequest(
        transactionId: 'ORIGINAL_TRANS_ID',
        amount:        150.00,
        currency:      'SAR',
        reason:        'Customer requested refund',
    ));

    echo "\nRefund Status: {$refund->status}\n";
    echo "Refund Amount: {$refund->amount} {$refund->currency}\n";
} catch (\Exception $e) {
    echo "Error: {$e->getMessage()}\n";
}

// ─── 6. Recurring Payment ───────────────────────────────────

try {
    $recurring = $gateway->recurring(new PaymentRequest(
        amount:         75.50,
        currency:       'SAR',
        orderId:        'ORD-2026-RECURRING',
        description:    'Monthly subscription',
        recurringToken: 'FIRST_TRANSACTION_ID',
    ));

    echo "\nRecurring Payment: {$recurring->status}\n";
} catch (\Exception $e) {
    echo "Error: {$e->getMessage()}\n";
}

// ─── 7. Apple Pay ───────────────────────────────────────────

try {
    $applePay = $gateway->applePay()->sale(
        amount:           99.00,
        currency:         'SAR',
        orderId:          'ORD-APPLE-001',
        orderDescription: 'Apple Pay Purchase',
        applePayToken:    'BASE64_ENCODED_APPLE_PAY_TOKEN',
        payerEmail:       'khalid@example.com',
    );

    echo "\nApple Pay: {$applePay->status}\n";
} catch (\Exception $e) {
    echo "Error: {$e->getMessage()}\n";
}
