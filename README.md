# 💳 Payment Gateway PHP Library

[![PHP Version](https://img.shields.io/badge/PHP-%5E8.1-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Composer](https://img.shields.io/badge/Composer-azozzalfiras%2Fpayment--gateway-orange.svg)](https://packagist.org/packages/azozzalfiras/payment-gateway)

A **unified**, **extensible** PHP Composer package for integrating with **10 MENA-region payment gateways**. Built with clean architecture, full API coverage, zero external dependencies, and enterprise-grade security.

## ✨ Supported Gateways

| Gateway | Files | Features | Region |
|---------|:-----:|----------|--------|
| **MyFatoorah** | 5 | Payments, Sessions, Invoices, Customers, Webhooks | 🇰🇼🇸🇦🇦🇪🇧🇭🇶🇦🇴🇲🇪🇬🇯🇴 |
| **Paylink** | 5 | Invoices, Digital Products, Reconciliation, Webhooks | 🇸🇦 |
| **EdfaPay** | 6 | Checkout, Embedded S2S, Apple Pay, Recurring, Refunds | 🇸🇦 MENA |
| **Tap** | 7 | Charges, Authorize, Refunds, Invoices, Customers, Tokens | 🇰🇼🇸🇦🇦🇪🇧🇭🇶🇦🇴🇲🇪🇬🇯🇴 |
| **ClickPay** | 3 | Hosted Page, Auth/Capture/Void/Refund, Tokenization | 🇸🇦🇦🇪🇪🇬🇴🇲🇯🇴🇵🇸 |
| **Tamara** | 4 | BNPL Checkout, Order Authorize/Capture/Cancel, Refunds | 🇸🇦🇦🇪🇰🇼🇧🇭🇶🇦 |
| **Thawani** | 4 | Checkout Sessions, Payment Intents, Customers | 🇴🇲 |
| **Fatora** | 4 | Checkout, Verify, Refunds, Recurring (Tokenization) | 🇸🇦🇦🇪🇶🇦🇧🇭🇰🇼🇴🇲🇮🇶🇯🇴🇪🇬 |
| **Payzaty** | 3 | Checkout, Mada/VISA/MC/Apple Pay/STC Pay | 🇸🇦 |
| **Payzah** | 3 | Checkout, K-Net/VISA/MC/Apple Pay/Amex, Multi-vendor | 🇰🇼 |

> **44 PHP classes** across 10 gateways, with consistent multi-file architecture.

## 📦 Installation

```bash
composer require azozzalfiras/payment-gateway
```

**Requirements:** PHP 8.1+, ext-curl, ext-json, ext-mbstring

## 🚀 Quick Start

### Using Enums (Type-Safe)

```php
use AzozzALFiras\PaymentGateway\PaymentGateway;
use AzozzALFiras\PaymentGateway\Enums\Gateway;
use AzozzALFiras\PaymentGateway\Enums\PaymentStatus;
use AzozzALFiras\PaymentGateway\Enums\Currency;

// Create gateway from enum
$gateway = PaymentGateway::create(Gateway::TAP->value, [
    'secret_key' => 'sk_test_xxx',
    'testMode'   => true,
]);

// Gateway metadata
echo Gateway::TAP->label();      // "Tap Payments"
echo Gateway::TAP->countries();   // ['KWT', 'SAU', 'ARE', ...]
echo Gateway::TAP->supportsRefund(); // true

// Normalize any vendor status to unified enum
$status = PaymentStatus::normalize('CAPTURED'); // PaymentStatus::CAPTURED
$status->isSuccessful(); // true
$status->isRefundable(); // true

// Currency helpers (Thawani uses baisa)
$amountInBaisa = Currency::OMR->toSmallestUnit(5.500); // 5500
echo Currency::SAR->format(100.50); // "100.50 SAR"
```

### Tap Payments

```php
use AzozzALFiras\PaymentGateway\DTOs\PaymentRequest;
use AzozzALFiras\PaymentGateway\DTOs\Customer;

$gateway = PaymentGateway::tap([
    'secret_key' => 'sk_test_xxx',
    'testMode'   => true,
]);

$response = $gateway->purchase(new PaymentRequest(
    amount:      100.00,
    currency:    'SAR',
    orderId:     'ORDER-001',
    description: 'Premium Subscription',
    callbackUrl: 'https://yoursite.com/callback',
    customer:    new Customer(name: 'Ahmed', email: 'ahmed@example.com'),
));

// Access sub-modules directly
$gateway->charges()->list();
$gateway->authorizations()->create([...]);
$gateway->customers()->create([...]);
$gateway->invoices()->create([...]);
$gateway->tokens()->create([...]);
```

### MyFatoorah (Multi-Country)

```php
$gateway = PaymentGateway::myfatoorah([
    'api_key'  => 'YOUR_API_KEY',
    'country'  => 'SAU',       // KWT, SAU, ARE, QAT, BHR, OMN, JOR, EGY
    'testMode' => true,
]);
```

### ClickPay (Multi-Region)

```php
$gateway = PaymentGateway::clickpay([
    'server_key' => 'YOUR_SERVER_KEY',
    'profile_id' => 'YOUR_PROFILE_ID',
    'region'     => 'SAU',
]);

// Access transactions sub-module
$gateway->transactions()->authorize([...]);
$gateway->transactions()->capture($ref, 100.00);
$gateway->transactions()->void($ref, 100.00);
```

### Tamara (BNPL)

```php
$gateway = PaymentGateway::tamara([
    'api_token' => 'YOUR_API_TOKEN',
    'testMode'  => true,
]);

// Order lifecycle
$gateway->orders()->authorise($orderId);
$gateway->orders()->capture($orderId, 500.00, 'SAR');
$gateway->orders()->cancel($orderId, 500.00);
```

### Thawani (Oman)

```php
$gateway = PaymentGateway::thawani([
    'secret_key'      => 'YOUR_SECRET_KEY',
    'publishable_key' => 'YOUR_PUBLISHABLE_KEY',
]);

// Amounts auto-converted to baisa (1 OMR = 1000)
$gateway->customers()->create([...]);
$gateway->sessions()->retrieve($sessionId);
```

### Fatora (Recurring)

```php
$gateway = PaymentGateway::fatora(['api_key' => 'YOUR_KEY']);

// Save card + recurring
$response = $gateway->purchase(new PaymentRequest(
    amount: 50.00, currency: 'SAR', orderId: 'ORD-001',
    callbackUrl: 'https://yoursite.com/callback',
    metadata: ['save_token' => true],
));

// Charge saved token
$gateway->recurringPayments()->charge(['token' => $savedToken, ...]);
```

### Payzaty / Payzah

```php
$payzaty = PaymentGateway::payzaty(['account_no' => '...', 'secret_key' => '...']);
$payzah  = PaymentGateway::payzah(['private_key' => '...']);
```

## 🏗️ Architecture

```
src/
├── PaymentGateway.php              ← Factory (10 drivers)
├── Enums/
│   ├── Gateway.php                 ← Gateway enum (10 drivers, metadata)
│   ├── PaymentStatus.php           ← Unified status (12 states, normalize())
│   ├── TransactionType.php         ← Transaction types (8 types)
│   └── Currency.php                ← MENA currencies (11, with baisa/fils conversion)
├── Contracts/
│   ├── GatewayInterface.php        ← Core interface
│   ├── SupportsRefund.php
│   ├── SupportsRecurring.php
│   ├── SupportsWebhook.php
│   └── SupportsInvoice.php
├── Config/GatewayConfig.php        ← Immutable configuration
├── DTOs/                           ← 8 Data Transfer Objects
├── Exceptions/                     ← 4 typed exceptions
├── Http/HttpClient.php             ← Zero-dependency cURL client
├── Security/
│   ├── SignatureVerifier.php       ← HMAC-SHA256/512, MD5, Bearer token
│   └── PayloadSanitizer.php        ← XSS, injection, card masking
├── Support/
│   ├── Arr.php                     ← Array helpers
│   └── Currency.php                ← Currency utility
└── Gateways/
    ├── MyFatoorah/     (5 classes)  ← Gateway, Session, Invoice, Customer, Webhook
    ├── Paylink/        (5 classes)  ← Gateway, Auth, Invoice, Reconcile, Webhook
    ├── EdfaPay/        (6 classes)  ← Gateway, Checkout, Embedded, ApplePay, Hash, Webhook
    ├── Tap/            (7 classes)  ← Gateway, Charge, Authorize, Customer, Invoice, Token, Webhook
    ├── ClickPay/       (3 classes)  ← Gateway, Transaction, Webhook
    ├── Tamara/         (4 classes)  ← Gateway, Checkout, Order, Webhook
    ├── Thawani/        (4 classes)  ← Gateway, Session, Customer, Webhook
    ├── Fatora/         (4 classes)  ← Gateway, Checkout, Recurring, Webhook
    ├── Payzaty/        (3 classes)  ← Gateway, Checkout, Webhook
    └── Payzah/         (3 classes)  ← Gateway, Payment, Webhook
```

## 🔒 Security Architecture

### Signature Verification

All webhook signatures use the centralized `SignatureVerifier`:

```php
use AzozzALFiras\PaymentGateway\Security\SignatureVerifier;

// HMAC-SHA256 (Tap, ClickPay, Thawani, Fatora, Payzaty)
SignatureVerifier::verifyHmacSha256($payload, $signature, $secret);

// Bearer Token (Tamara)
SignatureVerifier::verifyBearerToken($authHeader, $expectedToken);

// MD5 Hash (EdfaPay)
SignatureVerifier::verifyMd5($computed, $received);
```

**Security features:**
- ✅ **Timing-safe comparison** — All signatures use `hash_equals()` to prevent timing attacks
- ✅ **Centralized verification** — No duplicated crypto logic across gateways
- ✅ **Multi-algorithm support** — HMAC-SHA256, HMAC-SHA512, MD5, Bearer Token

### Input Sanitization

```php
use AzozzALFiras\PaymentGateway\Security\PayloadSanitizer;

$cleanEmail = PayloadSanitizer::email($userInput);       // Sanitized email
$cleanPhone = PayloadSanitizer::phone($userInput);       // Digits & + only
$cleanUrl   = PayloadSanitizer::url($userInput);         // HTTPS validation
$amount     = PayloadSanitizer::amount($userInput);      // Positive float
$masked     = PayloadSanitizer::maskCardNumber($card);   // 512345****2346
$cleanMeta  = PayloadSanitizer::metadata($userArray);    // Recursive XSS clean
$ip         = PayloadSanitizer::ip($userInput);          // Valid IP only
```

### Security Best Practices

1. **Never log raw card numbers** — use `PayloadSanitizer::maskCardNumber()`
2. **Always verify webhooks** — call `$gateway->verifyWebhook()` before processing
3. **Use HTTPS** for all callback/return URLs
4. **Store API keys** in environment variables, never in code
5. **Rotate keys** if any key may have been compromised
6. **IP Whitelist** gateway webhook IPs when supported

## 🗄️ Database Schema Guide

Here's a recommended database schema for storing transactions across all 10 gateways:

### Transactions Table

```sql
CREATE TABLE payment_transactions (
    id                BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    
    -- Gateway identification
    gateway           VARCHAR(20) NOT NULL COMMENT 'myfatoorah, tap, clickpay, etc.',
    gateway_txn_id    VARCHAR(255) NOT NULL COMMENT 'Gateway transaction/charge ID',
    
    -- Order reference
    order_id          VARCHAR(100) NOT NULL,
    
    -- Transaction details
    type              ENUM('sale','auth','capture','void','refund','recurring','checkout','invoice') NOT NULL DEFAULT 'sale',
    status            ENUM('pending','initiated','authorized','captured','paid','failed','cancelled','refunded','partial_refund','expired','voided','declined') NOT NULL DEFAULT 'pending',
    amount            DECIMAL(15, 3) NOT NULL COMMENT '3 decimals for KWD/BHD/OMR',
    currency          CHAR(3) NOT NULL COMMENT 'ISO 4217',
    
    -- Refund tracking
    refund_id         VARCHAR(255) NULL,
    refund_amount     DECIMAL(15, 3) NULL,
    parent_txn_id     BIGINT UNSIGNED NULL COMMENT 'For refunds/captures: references original transaction',
    
    -- Customer info (optional)
    customer_name     VARCHAR(255) NULL,
    customer_email    VARCHAR(255) NULL,
    customer_phone    VARCHAR(50) NULL,
    
    -- Gateway URLs
    payment_url       TEXT NULL COMMENT 'Redirect URL for hosted checkout',
    callback_url      TEXT NULL,
    
    -- Recurring/Token
    recurring_token   VARCHAR(255) NULL COMMENT 'Saved card token for recurring',
    
    -- Raw data
    gateway_response  JSON NULL COMMENT 'Full gateway response for debugging',
    webhook_payload   JSON NULL COMMENT 'Latest webhook data',
    
    -- Metadata
    metadata          JSON NULL COMMENT 'Custom merchant metadata',
    notes             TEXT NULL,
    ip_address        VARCHAR(45) NULL,
    
    -- Timestamps
    paid_at           TIMESTAMP NULL,
    refunded_at       TIMESTAMP NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_gateway_txn (gateway, gateway_txn_id),
    INDEX idx_order (order_id),
    INDEX idx_status (status),
    INDEX idx_customer (customer_email),
    INDEX idx_created (created_at),
    FOREIGN KEY (parent_txn_id) REFERENCES payment_transactions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Gateway Configurations Table

```sql
CREATE TABLE payment_gateway_configs (
    id               INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    gateway          VARCHAR(20) NOT NULL UNIQUE,
    display_name     VARCHAR(100) NOT NULL,
    is_active        BOOLEAN NOT NULL DEFAULT TRUE,
    is_test_mode     BOOLEAN NOT NULL DEFAULT FALSE,
    credentials      JSON NOT NULL COMMENT 'Encrypted API keys',
    supported_currencies JSON NULL,
    supported_countries  JSON NULL,
    webhook_secret   VARCHAR(255) NULL,
    settings         JSON NULL COMMENT 'Gateway-specific config (region, language, etc.)',
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Example: Loading Gateway from Database

```php
use AzozzALFiras\PaymentGateway\PaymentGateway;
use AzozzALFiras\PaymentGateway\Enums\Gateway;

// Fetch from database
$row = $db->query("SELECT * FROM payment_gateway_configs WHERE gateway = ? AND is_active = 1", ['tap'])->fetch();

$credentials = json_decode($decrypt($row['credentials']), true);
$settings    = json_decode($row['settings'] ?? '{}', true);

$gateway = PaymentGateway::create($row['gateway'], array_merge($credentials, $settings, [
    'testMode' => (bool) $row['is_test_mode'],
]));

// Save transaction result
$response = $gateway->purchase($request);

$db->insert('payment_transactions', [
    'gateway'         => Gateway::TAP->value,
    'gateway_txn_id'  => $response->transactionId,
    'order_id'        => 'ORD-001',
    'type'            => 'sale',
    'status'          => PaymentStatus::normalize($response->status)->value,
    'amount'          => $response->amount,
    'currency'        => $response->currency,
    'payment_url'     => $response->paymentUrl,
    'gateway_response'=> json_encode($response->rawResponse),
]);
```

## 🏭 Factory Pattern

```php
// Generic factory
$gateway = PaymentGateway::create('tap', ['secret_key' => '...']);

// Typed factory (full IDE autocomplete)
$gateway = PaymentGateway::tap([...]);
$gateway = PaymentGateway::clickpay([...]);
$gateway = PaymentGateway::tamara([...]);
$gateway = PaymentGateway::thawani([...]);
$gateway = PaymentGateway::fatora([...]);
$gateway = PaymentGateway::payzaty([...]);
$gateway = PaymentGateway::payzah([...]);
$gateway = PaymentGateway::myfatoorah([...]);
$gateway = PaymentGateway::paylink([...]);
$gateway = PaymentGateway::edfapay([...]);

// List all 10 drivers
PaymentGateway::getAvailableDrivers();
```

## 🔌 Feature Matrix

| Feature | MyFatoorah | Paylink | EdfaPay | Tap | ClickPay | Tamara | Thawani | Fatora | Payzaty | Payzah |
|---------|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| Purchase | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Status | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Refund | ✅ | — | ✅ | ✅ | ✅ | ✅ | — | ✅ | — | — |
| Recurring | — | — | ✅ | ✅ | ✅ | — | ✅ | ✅ | — | — |
| Auth/Capture | ✅ | — | ✅ | ✅ | ✅ | ✅ | — | — | — | — |
| Invoices | ✅ | ✅ | — | ✅ | — | — | — | — | — | — |
| Webhook | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

## 🔐 Webhook Handling

```php
$payload = json_decode(file_get_contents('php://input'), true);
$headers = getallheaders();

$webhook = $gateway->handleWebhook($payload, $headers);

if ($webhook->isValid) {
    $status = PaymentStatus::normalize($webhook->status);
    echo "Transaction: {$webhook->transactionId}\n";
    echo "Status: {$status->value} (successful: " . ($status->isSuccessful() ? 'yes' : 'no') . ")\n";
}
```

## 🧪 Testing

```bash
composer install
composer test        # Run PHPUnit tests
composer analyse     # Run PHPStan analysis
```

## 📄 License

MIT License — see [LICENSE](LICENSE) file.

## 👨‍💻 Author

**AzozzALFiras** — [GitHub](https://github.com/AzozzALFiras)
