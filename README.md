# 💳 Payment Gateway PHP Library

[![PHP Version](https://img.shields.io/badge/PHP-%5E8.1-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Composer](https://img.shields.io/badge/Composer-azozzalfiras%2Fpayment--gateway-orange.svg)](https://packagist.org/packages/azozzalfiras/payment-gateway)

A **unified**, **extensible** PHP Composer package for integrating with **10 MENA-region payment gateways**. Built with clean architecture, full API coverage, zero external dependencies, and enterprise-grade security.

---

## 📑 Table of Contents

- [Supported Gateways](#supported-gateways)
- [Installation](#installation)
- [Quick Start & Enums](#quick-start)
- [Gateway Reference](#gateway-reference)
  - [MyFatoorah](#myfatoorah)
  - [Paylink](#paylink)
  - [EdfaPay](#edfapay)
  - [Tap Payments](#tap-payments)
  - [ClickPay](#clickpay)
  - [Tamara (BNPL)](#tamara)
  - [Thawani](#thawani)
  - [Fatora](#fatora)
  - [Payzaty](#payzaty)
  - [Payzah](#payzah)
  - [Stripe](#stripe)
- [Architecture](#architecture)
- [Factory Pattern](#factory-pattern)
- [Feature Matrix](#feature-matrix)
- [Security Architecture](#security-architecture)
- [Webhook Handling](#webhook-handling)
- [Database Integration Guide](#database-integration-guide)
- [Testing](#testing)

---

<a id="supported-gateways"></a>

## ✨ Supported Gateways

<table>
  <tr>
    <td align="center"><img src="assets/logos/myfatoorah.png" width="80"><br><b>MyFatoorah</b></td>
    <td align="center"><img src="assets/logos/paylink.png" width="80"><br><b>Paylink</b></td>
    <td align="center"><img src="assets/logos/edfapay.png" width="80"><br><b>EdfaPay</b></td>
    <td align="center"><img src="assets/logos/tap.png" width="80"><br><b>Tap</b></td>
    <td align="center"><img src="assets/logos/clickpay.png" width="80"><br><b>ClickPay</b></td>
  </tr>
  <tr>
    <td align="center"><img src="assets/logos/tamara.png" width="80"><br><b>Tamara</b></td>
    <td align="center"><img src="assets/logos/thawani.png" width="80"><br><b>Thawani</b></td>
    <td align="center"><img src="assets/logos/fatorah.png" width="80"><br><b>Fatora</b></td>
    <td align="center"><img src="assets/logos/payzaty.png" width="80"><br><b>Payzaty</b></td>
    <td align="center"><img src="assets/logos/payzah.png" width="80"><br><b>Payzah</b></td>
  </tr>
  <tr>
    <td align="center" colspan="5"><img src="assets/logos/stripe.png" width="80"><br><b>Stripe</b></td>
  </tr>
</table>

| Gateway | Classes | Key Features | Authentication | Supported Regions |
|---------|:-------:|-------------|----------------|-------------------|
| **MyFatoorah** | 5 | Payments, Sessions, Invoices, Customers, Webhooks | API Key (Bearer) | 🇰🇼 KWT · 🇸🇦 SAU · 🇦🇪 ARE · 🇧🇭 BHR · 🇶🇦 QAT · 🇴🇲 OMN · 🇪🇬 EGY · 🇯🇴 JOR |
| **Paylink** | 5 | Invoices, Digital Products, Reconciliation, Webhooks | API ID + Secret Key | 🇸🇦 SAU |
| **EdfaPay** | 6 | Checkout, Embedded S2S, Apple Pay, Recurring, Auth/Capture, Refunds | Client Key + Password | 🇸🇦 SAU · MENA |
| **Tap** | 7 | Charges, Authorize/Void, Refunds, Invoices, Customers, Tokens | Secret Key (Bearer) | 🇰🇼 KWT · 🇸🇦 SAU · 🇦🇪 ARE · 🇧🇭 BHR · 🇶🇦 QAT · 🇴🇲 OMN · 🇪🇬 EGY · 🇯🇴 JOR |
| **ClickPay** | 3 | Hosted Page, Auth/Capture/Void/Refund, Tokenization | Server Key | 🇸🇦 SAU · 🇦🇪 ARE · 🇪🇬 EGY · 🇴🇲 OMN · 🇯🇴 JOR |
| **Tamara** | 4 | BNPL Checkout, Order Authorize/Capture/Cancel, Refunds | API Token (Bearer) | 🇸🇦 SAU · 🇦🇪 ARE · 🇰🇼 KWT · 🇧🇭 BHR · 🇶🇦 QAT |
| **Thawani** | 4 | Checkout Sessions, Payment Intents, Customer Management | Secret + Publishable Key | 🇴🇲 OMN |
| **Fatora** | 4 | Checkout, Verify, Refunds, Recurring (Card Tokenization) | API Key | 🇸🇦 SAU · 🇦🇪 ARE · 🇶🇦 QAT · 🇧🇭 BHR · 🇰🇼 KWT · 🇴🇲 OMN · 🇮🇶 IRQ · 🇯🇴 JOR · 🇪🇬 EGY |
| **Payzaty** | 3 | Secure Checkout, Mada/VISA/MC/Apple Pay/STC Pay | Account No + Secret Key | 🇸🇦 SAU |
| **Payzah** | 3 | Hosted Checkout, K-Net/VISA/MC/Apple Pay/Amex, Multi-vendor | Private Key | 🇰🇼 KWT |
| **Stripe** | 6 | Checkout Sessions, Charges, Customers, Refunds, PaymentIntents, Webhooks | Secret Key (Bearer) | 🌍 46+ countries |

> **50 PHP classes** across 11 gateways, with consistent multi-file architecture per gateway.

---

<a id="installation"></a>

## 📦 Installation

```bash
composer require azozzalfiras/payment-gateway
```

**System Requirements:**

| Requirement | Version |
|-------------|---------|
| PHP | ≥ 8.1 |
| ext-curl | Required |
| ext-json | Required |
| ext-mbstring | Required |

---

<a id="quick-start"></a>

## 🚀 Quick Start

### Using Enums (Type-Safe Gateway Selection)

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

// Gateway metadata from enum
echo Gateway::TAP->label();           // "Tap Payments"
echo Gateway::TAP->countries();       // ['KWT', 'SAU', 'ARE', ...]
echo Gateway::TAP->supportsRefund();  // true
echo Gateway::TAP->isBNPL();          // false

// Normalize any vendor-specific status string to unified enum
$status = PaymentStatus::normalize('CAPTURED'); // PaymentStatus::CAPTURED
$status->isSuccessful(); // true
$status->isRefundable(); // true
$status->isFinal();      // true

// Currency helpers with smallest-unit conversion
$amount = Currency::OMR->toSmallestUnit(5.500);   // 5500 (baisa)
$back   = Currency::OMR->fromSmallestUnit(5500);  // 5.5
echo Currency::SAR->format(100.50);                // "100.50 SAR"
echo Currency::KWD->decimals();                    // 3
```

---

<a id="gateway-reference"></a>

## 📖 Gateway Reference

Each gateway section below provides: configuration, full code example, and available sub-modules.

---

<a id="myfatoorah"></a>

### <img src="assets/logos/myfatoorah.png" width="28"> MyFatoorah

| Property | Details |
|----------|---------|
| **Classes** | `MyFatoorahGateway`, `MyFatoorahSession`, `MyFatoorahInvoice`, `MyFatoorahCustomer`, `MyFatoorahWebhook` |
| **Authentication** | API Key (Bearer Token) — separate key per country |
| **Regions** | Kuwait, Saudi Arabia, UAE, Bahrain, Qatar, Oman, Egypt, Jordan |
| **Currencies** | KWD, SAR, AED, BHD, QAR, OMR, EGP, JOD |
| **Test Mode** | Separate test API base per country |

```php
$gateway = PaymentGateway::myfatoorah([
    'api_key'  => 'YOUR_API_KEY',
    'country'  => 'SAU',       // KWT, SAU, ARE, QAT, BHR, OMN, JOR, EGY
    'testMode' => true,
]);

$response = $gateway->purchase(new PaymentRequest(
    amount:      100.00,
    currency:    'SAR',
    orderId:     'ORDER-001',
    description: 'Premium Subscription',
    callbackUrl: 'https://yoursite.com/callback',
    customer:    new Customer(name: 'Ahmed', email: 'ahmed@example.com'),
));

// Sub-modules
$gateway->sessions()->create(100.00);
$gateway->customers()->getDetails($ref);
```

---

<a id="paylink"></a>

### <img src="assets/logos/paylink.png" width="28"> Paylink

| Property | Details |
|----------|---------|
| **Classes** | `PaylinkGateway`, `PaylinkAuth`, `PaylinkInvoice`, `PaylinkReconcile`, `PaylinkWebhook` |
| **Authentication** | API ID + Secret Key (OAuth-style token) |
| **Regions** | Saudi Arabia |
| **Currencies** | SAR |

```php
$gateway = PaymentGateway::paylink([
    'api_id'     => 'YOUR_API_ID',
    'secret_key' => 'YOUR_SECRET_KEY',
    'testMode'   => true,
]);

$invoice = $gateway->createInvoice(new InvoiceRequest(
    amount: 250.00, currency: 'SAR', orderId: 'INV-001',
    callbackUrl: 'https://yoursite.com/callback',
    customer: new Customer(name: 'Mohammed', email: 'mohammed@example.com'),
    items: [['name' => 'Web Design', 'price' => 250.00, 'quantity' => 1]],
));

// Sub-modules
$gateway->reconcile()->getByDateRange($from, $to);
$gateway->reconcile()->getSettlements($from, $to);
```

---

<a id="edfapay"></a>

### <img src="assets/logos/edfapay.png" width="28"> EdfaPay

| Property | Details |
|----------|---------|
| **Classes** | `EdfaPayGateway`, `EdfaPayCheckout`, `EdfaPayEmbedded`, `EdfaPayApplePay`, `EdfaPayHash`, `EdfaPayWebhook` |
| **Authentication** | Client Key + Password (HMAC signature) |
| **Regions** | Saudi Arabia, MENA |
| **Features** | Hosted Checkout, Server-to-Server (Direct Card), Apple Pay, Recurring, Auth/Capture |

```php
$gateway = PaymentGateway::edfapay([
    'client_key' => 'YOUR_CLIENT_KEY',
    'password'   => 'YOUR_SECRET_PASSWORD',
    'testMode'   => true,
]);

// Hosted Checkout
$response = $gateway->purchase(new PaymentRequest(...));

// Embedded S2S (Direct Card Payment)
$result = $gateway->embedded()->sale([...]);
$gateway->embedded()->authorize([...]);
$gateway->embedded()->capture($transId);

// Apple Pay
$gateway->applePay()->sale([...]);
```

---

<a id="tap-payments"></a>

### <img src="assets/logos/tap.png" width="28"> Tap Payments

| Property | Details |
|----------|---------|
| **Classes** | `TapGateway`, `TapCharge`, `TapAuthorize`, `TapCustomer`, `TapInvoice`, `TapToken`, `TapWebhook` |
| **Authentication** | Secret Key (Bearer Token) |
| **Regions** | Kuwait, Saudi Arabia, UAE, Bahrain, Qatar, Oman, Egypt, Jordan |
| **Currencies** | KWD, SAR, AED, BHD, QAR, OMR, EGP |

```php
$gateway = PaymentGateway::tap([
    'secret_key'     => 'sk_test_xxx',
    'webhook_secret' => 'whsec_xxx',   // Optional: for webhook verification
    'testMode'       => true,
]);

$response = $gateway->purchase(new PaymentRequest(
    amount:      100.00,
    currency:    'SAR',
    orderId:     'ORDER-001',
    description: 'Premium Plan',
    callbackUrl: 'https://yoursite.com/callback',
    customer:    new Customer(name: 'Ahmed', email: 'ahmed@example.com'),
));

// Sub-modules — full API coverage
$gateway->charges()->create($request);      // Create charge
$gateway->charges()->retrieve($chargeId);   // Get charge details
$gateway->charges()->update($id, $data);    // Update charge
$gateway->charges()->list($filters);        // List charges

$gateway->authorizations()->create($params);  // Pre-auth hold
$gateway->authorizations()->void($authId);    // Release hold

$gateway->customers()->create($data);
$gateway->customers()->retrieve($id);
$gateway->customers()->update($id, $data);
$gateway->customers()->delete($id);

$gateway->invoices()->create($data);
$gateway->invoices()->retrieve($id);

$gateway->tokens()->create($cardData);  // Tokenize a card
```

---

<a id="clickpay"></a>

### <img src="assets/logos/clickpay.png" width="28"> ClickPay

| Property | Details |
|----------|---------|
| **Classes** | `ClickPayGateway`, `ClickPayTransaction`, `ClickPayWebhook` |
| **Authentication** | Server Key (header) + Profile ID |
| **Regions** | SAU, ARE, EGY, OMN, JOR (region-specific endpoints) |
| **Currencies** | SAR, AED, EGP, OMR, JOD |

```php
$gateway = PaymentGateway::clickpay([
    'server_key' => 'YOUR_SERVER_KEY',
    'profile_id' => 'YOUR_PROFILE_ID',
    'region'     => 'SAU',    // SAU, ARE, EGY, OMN, JOR
    'testMode'   => true,
]);

$response = $gateway->purchase(new PaymentRequest(...));

// Sub-module: Transaction lifecycle
$gateway->transactions()->authorize($params);             // Pre-auth
$gateway->transactions()->capture($ref, 100.00);          // Capture
$gateway->transactions()->void($ref, 100.00);             // Void
$gateway->transactions()->query($ref);                    // Status query
```

---

<a id="tamara"></a>

### <img src="assets/logos/tamara.png" width="28"> Tamara (BNPL)

| Property | Details |
|----------|---------|
| **Classes** | `TamaraGateway`, `TamaraCheckout`, `TamaraOrder`, `TamaraWebhook` |
| **Authentication** | API Token (Bearer) |
| **Type** | Buy Now Pay Later (Installments) |
| **Regions** | Saudi Arabia, UAE, Kuwait, Bahrain, Qatar |

```php
$gateway = PaymentGateway::tamara([
    'api_token'          => 'YOUR_API_TOKEN',
    'notification_token' => 'YOUR_WEBHOOK_TOKEN',  // Optional
    'testMode'           => true,
]);

$response = $gateway->purchase(new PaymentRequest(
    amount: 500.00, currency: 'SAR', orderId: 'ORD-001',
    callbackUrl: 'https://yoursite.com/callback',
    customer: new Customer(name: 'Sara', email: 'sara@example.com'),
    metadata: ['payment_type' => 'PAY_BY_INSTALMENTS'],
));

// Sub-modules: Order lifecycle
$gateway->orders()->authorise($orderId);
$gateway->orders()->capture($orderId, 500.00, 'SAR');
$gateway->orders()->cancel($orderId, 500.00);
$gateway->orders()->refund($orderId, 250.00, 'SAR', 'Partial refund');

$gateway->checkouts()->paymentOptions($orderData);
```

---

<a id="thawani"></a>

### <img src="assets/logos/thawani.png" width="28"> Thawani

| Property | Details |
|----------|---------|
| **Classes** | `ThawaniGateway`, `ThawaniSession`, `ThawaniCustomer`, `ThawaniWebhook` |
| **Authentication** | Secret Key + Publishable Key |
| **Region** | Oman exclusively |
| **Currency** | OMR (amounts converted to baisa: 1 OMR = 1000 baisa) |

```php
$gateway = PaymentGateway::thawani([
    'secret_key'      => 'YOUR_SECRET_KEY',
    'publishable_key' => 'YOUR_PUBLISHABLE_KEY',
    'testMode'        => true,
]);

// Amounts auto-converted to baisa internally
$response = $gateway->purchase(new PaymentRequest(
    amount: 5.500, currency: 'OMR', orderId: 'ORD-001',
    callbackUrl: 'https://yoursite.com/callback',
));

// Sub-modules
$gateway->sessions()->retrieve($sessionId);
$gateway->customers()->create($data);
$gateway->customers()->getPaymentMethods($customerId);

// Payment intents (charge saved cards)
$gateway->createPaymentIntent(5500, 'ref-001', $paymentMethodId, $returnUrl);
```

---

<a id="fatora"></a>

### <img src="assets/logos/fatorah.png" width="28"> Fatora

| Property | Details |
|----------|---------|
| **Classes** | `FatoraGateway`, `FatoraCheckout`, `FatoraRecurring`, `FatoraWebhook` |
| **Authentication** | API Key |
| **Regions** | SAU, ARE, QAT, BHR, KWT, OMN, IRQ, JOR, EGY |
| **Special** | Card tokenization for recurring payments |

```php
$gateway = PaymentGateway::fatora([
    'api_key'  => 'YOUR_API_KEY',
    'testMode' => true,
]);

// Save card token for recurring
$response = $gateway->purchase(new PaymentRequest(
    amount: 50.00, currency: 'SAR', orderId: 'ORD-001',
    callbackUrl: 'https://yoursite.com/callback',
    metadata: ['save_token' => true],
));
$savedToken = $response->recurringToken;

// Charge saved token (recurring)
$gateway->recurring(new PaymentRequest(
    amount: 50.00, currency: 'SAR', orderId: 'REC-001',
    recurringToken: $savedToken,
));

// Sub-modules
$gateway->checkouts()->verify($orderId);
$gateway->recurringPayments()->deactivateToken($token);
```

---

<a id="payzaty"></a>

### <img src="assets/logos/payzaty.png" width="28"> Payzaty

| Property | Details |
|----------|---------|
| **Classes** | `PayzatyGateway`, `PayzatyCheckout`, `PayzatyWebhook` |
| **Authentication** | X-AccountNo + X-SecretKey headers |
| **Region** | Saudi Arabia |
| **Payment Methods** | Mada, VISA, MasterCard, Apple Pay, STC Pay |

```php
$gateway = PaymentGateway::payzaty([
    'account_no' => 'YOUR_ACCOUNT_NO',
    'secret_key' => 'YOUR_SECRET_KEY',
    'language'   => 'ar',    // Optional: ar/en
    'testMode'   => true,
]);

$response = $gateway->purchase(new PaymentRequest(
    amount: 200.00, currency: 'SAR', orderId: 'ORD-001',
    callbackUrl: 'https://yoursite.com/callback',
));

$gateway->checkouts()->status($paymentId);
```

---

<a id="payzah"></a>

### <img src="assets/logos/payzah.png" width="28"> Payzah

| Property | Details |
|----------|---------|
| **Classes** | `PayzahGateway`, `PayzahPayment`, `PayzahWebhook` |
| **Authentication** | Private Key |
| **Region** | Kuwait |
| **Payment Methods** | K-Net, VISA, MasterCard, Apple Pay, Amex |
| **Special** | Multi-vendor support |

```php
$gateway = PaymentGateway::payzah([
    'private_key' => 'YOUR_PRIVATE_KEY',
    'testMode'    => true,
]);

$response = $gateway->purchase(new PaymentRequest(
    amount: 15.000, currency: 'KWD', orderId: 'ORD-001',
    callbackUrl: 'https://yoursite.com/callback',
    metadata: [
        'payment_method' => 'knet',
        'vendors' => [['id' => 'V1', 'amount' => 10.000], ['id' => 'V2', 'amount' => 5.000]],
    ],
));

$gateway->payments()->status($paymentId);
```

---

<a id="stripe"></a>

### <img src="assets/logos/stripe.png" width="28"> Stripe

| Property | Details |
|----------|--------|
| **Classes** | `StripeGateway`, `StripeCheckout`, `StripeCharge`, `StripeCustomer`, `StripeRefund`, `StripeWebhook` |
| **Authentication** | Secret Key (Bearer Token) |
| **Regions** | 46+ countries globally (USA, GBR, DEU, FRA, ARE, SAU, etc.) |
| **Currencies** | 135+ currencies (USD, EUR, GBP, SAR, AED, KWD, BHD, etc.) |
| **Features** | Checkout Sessions, Charges, PaymentIntents, Customers, Refunds |

```php
$gateway = PaymentGateway::stripe([
    'secret_key'     => 'sk_test_xxx',
    'webhook_secret' => 'whsec_xxx',   // Optional
    'testMode'       => true,
]);

$response = $gateway->purchase(new PaymentRequest(
    amount:      99.99,
    currency:    'USD',
    orderId:     'ORDER-001',
    description: 'Pro Subscription',
    callbackUrl: 'https://yoursite.com/webhook/stripe',
    returnUrl:   'https://yoursite.com/success',
    cancelUrl:   'https://yoursite.com/cancel',
    customer:    new Customer(name: 'John', email: 'john@example.com'),
));

// Sub-modules — full API coverage
$gateway->checkouts()->create($params);       // Create checkout session
$gateway->checkouts()->retrieve($sessionId);  // Get session
$gateway->checkouts()->expire($sessionId);    // Cancel session

$gateway->charges()->create($params);         // Direct charge
$gateway->charges()->capture($chargeId);      // Capture pre-auth
$gateway->charges()->list(['limit' => 10]);   // List charges

$gateway->customers()->create($data);         // Create customer
$gateway->customers()->update($id, $data);    // Update
$gateway->customers()->delete($id);           // Delete

$gateway->refunds()->create(['payment_intent' => 'pi_xxx', 'amount' => 5000]);
$gateway->refunds()->retrieve($refundId);

// PaymentIntents (server-side)
$gateway->createPaymentIntent(['amount' => 5000, 'currency' => 'usd']);
$gateway->retrievePaymentIntent('pi_xxx');
```

---

<a id="architecture"></a>

## 🏗️ Architecture

```
src/
├── PaymentGateway.php              ← Factory (10 drivers)
├── Enums/
│   ├── Gateway.php                 ← Gateway enum (10 drivers + metadata)
│   ├── PaymentStatus.php           ← Unified status (12 states + normalize())
│   ├── TransactionType.php         ← Transaction types (8 types)
│   └── Currency.php                ← MENA currencies (11 + baisa/fils conversion)
├── Contracts/
│   ├── GatewayInterface.php        ← Core interface (purchase, status)
│   ├── SupportsRefund.php          ← Refund capability contract
│   ├── SupportsRecurring.php       ← Recurring capability contract
│   ├── SupportsWebhook.php         ← Webhook capability contract
│   └── SupportsInvoice.php         ← Invoice capability contract
├── Config/GatewayConfig.php        ← Immutable configuration object
├── DTOs/                           ← 8 Data Transfer Objects
│   ├── PaymentRequest.php
│   ├── PaymentResponse.php
│   ├── RefundRequest.php
│   ├── RefundResponse.php
│   ├── WebhookPayload.php
│   ├── Customer.php
│   ├── InvoiceRequest.php
│   └── InvoiceResponse.php
├── Exceptions/                     ← Typed exception hierarchy
│   ├── PaymentException.php        ← Base exception
│   ├── AuthenticationException.php
│   ├── ValidationException.php
│   └── ApiException.php
├── Http/HttpClient.php             ← Zero-dependency cURL client
├── Security/
│   ├── SignatureVerifier.php       ← HMAC-SHA256/512, MD5, Bearer token
│   └── PayloadSanitizer.php        ← XSS/injection prevention, card masking
├── Support/
│   ├── Arr.php                     ← Array dot-notation helpers
│   └── Currency.php                ← Currency utility
└── Gateways/
    ├── MyFatoorah/  (5 classes)    ← Gateway, Session, Invoice, Customer, Webhook
    ├── Paylink/     (5 classes)    ← Gateway, Auth, Invoice, Reconcile, Webhook
    ├── EdfaPay/     (6 classes)    ← Gateway, Checkout, Embedded, ApplePay, Hash, Webhook
    ├── Tap/         (7 classes)    ← Gateway, Charge, Authorize, Customer, Invoice, Token, Webhook
    ├── ClickPay/    (3 classes)    ← Gateway, Transaction, Webhook
    ├── Tamara/      (4 classes)    ← Gateway, Checkout, Order, Webhook
    ├── Thawani/     (4 classes)    ← Gateway, Session, Customer, Webhook
    ├── Fatora/      (4 classes)    ← Gateway, Checkout, Recurring, Webhook
    ├── Payzaty/     (3 classes)    ← Gateway, Checkout, Webhook
    ├── Payzah/      (3 classes)    ← Gateway, Payment, Webhook
    └── Stripe/      (6 classes)    ← Gateway, Checkout, Charge, Customer, Refund, Webhook
```

---

<a id="factory-pattern"></a>

## 🏭 Factory Pattern

The `PaymentGateway` class provides two ways to instantiate any gateway:

```php
use AzozzALFiras\PaymentGateway\PaymentGateway;

// 1. Generic factory — accepts any driver name string
$gateway = PaymentGateway::create('tap', ['secret_key' => '...']);

// 2. Typed static factory — full IDE autocomplete per gateway
$gateway = PaymentGateway::myfatoorah([...]);
$gateway = PaymentGateway::paylink([...]);
$gateway = PaymentGateway::edfapay([...]);
$gateway = PaymentGateway::tap([...]);
$gateway = PaymentGateway::clickpay([...]);
$gateway = PaymentGateway::tamara([...]);
$gateway = PaymentGateway::thawani([...]);
$gateway = PaymentGateway::fatora([...]);
$gateway = PaymentGateway::payzaty([...]);
$gateway = PaymentGateway::payzah([...]);
$gateway = PaymentGateway::stripe([...]);

// List all 11 available driver names
PaymentGateway::getAvailableDrivers();
// Returns: ['myfatoorah', 'paylink', 'edfapay', 'tap', 'clickpay', 'tamara', 'thawani', 'fatora', 'payzaty', 'payzah', 'stripe']
```

---

<a id="feature-matrix"></a>

## 🔌 Feature Matrix

| Feature | <img src="assets/logos/myfatoorah.png" width="20"> | <img src="assets/logos/paylink.png" width="20"> | <img src="assets/logos/edfapay.png" width="20"> | <img src="assets/logos/tap.png" width="20"> | <img src="assets/logos/clickpay.png" width="20"> | <img src="assets/logos/tamara.png" width="20"> | <img src="assets/logos/thawani.png" width="20"> | <img src="assets/logos/fatorah.png" width="20"> | <img src="assets/logos/payzaty.png" width="20"> | <img src="assets/logos/payzah.png" width="20"> | <img src="assets/logos/stripe.png" width="20"> |
|---------|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| Purchase | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Payment Status | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Refund | ✅ | — | ✅ | ✅ | ✅ | ✅ | — | ✅ | — | — | ✅ |
| Recurring | — | — | ✅ | ✅ | ✅ | — | ✅ | ✅ | — | — | ✅ |
| Auth/Capture | ✅ | — | ✅ | ✅ | ✅ | ✅ | — | — | — | — | ✅ |
| Invoices | ✅ | ✅ | — | ✅ | — | — | — | — | — | — | — |
| Customer CRUD | ✅ | — | — | ✅ | — | — | ✅ | — | — | — | ✅ |
| Tokenization | — | — | — | ✅ | ✅ | — | — | ✅ | — | — | ✅ |
| Webhook | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

---

<a id="security-architecture"></a>

## 🔒 Security Architecture

### Signature Verification

All webhook signatures use the centralized `SignatureVerifier` class, which provides **timing-safe comparison** via `hash_equals()` to prevent timing attacks:

```php
use AzozzALFiras\PaymentGateway\Security\SignatureVerifier;

// HMAC-SHA256 — used by Tap, ClickPay, Thawani, Fatora, Payzaty
SignatureVerifier::verifyHmacSha256($payload, $signature, $secret);

// HMAC-SHA512
SignatureVerifier::verifyHmacSha512($payload, $signature, $secret);

// Bearer Token — used by Tamara
SignatureVerifier::verifyBearerToken($authHeader, $expectedToken);

// MD5 Hash — used by EdfaPay
SignatureVerifier::verifyMd5($computed, $received);

// Extract signature from headers (case-insensitive)
SignatureVerifier::extractSignature($headers, ['Tap-Signature', 'tap-signature']);
```

### Input Sanitization

```php
use AzozzALFiras\PaymentGateway\Security\PayloadSanitizer;

PayloadSanitizer::string($input);          // Strip HTML/JS, trim
PayloadSanitizer::email($input);           // Sanitized email
PayloadSanitizer::phone($input);           // Digits, +, spaces only
PayloadSanitizer::url($input);             // HTTPS validation
PayloadSanitizer::amount($input);          // Positive float, max 3 decimals
PayloadSanitizer::ip($input);              // Valid IPv4/IPv6 only
PayloadSanitizer::maskCardNumber($card);   // 512345****2346
PayloadSanitizer::metadata($array);        // Recursive XSS clean
```

### Security Best Practices

1. **Never log raw card numbers** — use `PayloadSanitizer::maskCardNumber()`
2. **Always verify webhooks** — call `$gateway->verifyWebhook()` before processing
3. **Use HTTPS** for all callback/return URLs
4. **Store API keys** in environment variables, never in source code
5. **Rotate keys** immediately if any key may have been compromised
6. **IP Whitelist** gateway webhook IPs when the gateway supports it

---

<a id="webhook-handling"></a>

## 🔐 Webhook Handling

All 10 gateways support webhook processing through a unified interface:

```php
// In your webhook endpoint controller (e.g. POST /webhook/tap)
$payload = json_decode(file_get_contents('php://input'), true);
$headers = getallheaders();

// 1. Verify signature + parse payload
$webhook = $gateway->handleWebhook($payload, $headers);

// 2. Check validity
if (!$webhook->isValid) {
    http_response_code(401);
    exit('Invalid signature');
}

// 3. Use unified PaymentStatus enum
$status = PaymentStatus::normalize($webhook->status);

echo "Transaction: {$webhook->transactionId}\n";
echo "Order: {$webhook->orderId}\n";
echo "Amount: {$webhook->amount} {$webhook->currency}\n";
echo "Status: {$status->value}\n";
echo "Successful: " . ($status->isSuccessful() ? 'yes' : 'no') . "\n";
echo "Final: " . ($status->isFinal() ? 'yes' : 'no') . "\n";
```

---

<a id="database-integration-guide"></a>

## 🗄️ Database Integration Guide

> The full step-by-step guide is available in [docs/DATABASE.md](docs/DATABASE.md) — includes SQL schema, PaymentTransaction Model with enum casting, PaymentService class, and WebhookController.

**What's covered in the database guide:**
- **3 SQL tables** — `payment_transactions`, `payment_gateway_configs`, `payment_webhook_logs`
- **VARCHAR columns** instead of SQL `ENUM` — add new statuses in PHP without DB migrations
- **PaymentTransaction Model** — casts `VARCHAR` ↔ PHP backed enums (`Gateway`, `PaymentStatus`, `TransactionType`, `Currency`)
- **PaymentService** — `createPayment()`, `refundPayment()`, `loadGateway()` from DB
- **WebhookController** — handles webhooks from any gateway with audit logging
- **Full usage examples** — payment creation → redirect → webhook → status check → refund

---

<a id="testing"></a>

## 🧪 Testing

```bash
# Install dependencies
composer install

# Run PHPUnit test suite
composer test

# Run PHPStan static analysis (Level 5)
composer analyse

# Lint all PHP files
find src/ -name '*.php' -exec php -l {} \;
```

**Current test results:**
- ✅ PHP Lint: 72/72 files — zero syntax errors
- ✅ PHPUnit: 16 tests, 36 assertions — all passing
- ✅ PHPStan Level 5: 0 errors

---

## 📄 License

MIT License — see [LICENSE](LICENSE) file.

---

## 👨‍💻 Author

**AzozzALFiras** — [GitHub](https://github.com/AzozzALFiras)
