# Architecture

> **Back to [README](../README.md)**

```
src/
├── PaymentGateway.php              <- Factory (18 drivers)
├── Enums/
│   ├── Gateway.php                 <- Gateway enum (18 drivers + metadata + region)
│   ├── PaymentStatus.php           <- Unified status (12 states + normalize())
│   ├── TransactionType.php         <- Transaction types (8 types)
│   └── Currency.php                <- MENA currencies (11 + baisa/fils conversion)
├── Contracts/
│   ├── GatewayInterface.php        <- Core interface (purchase, status)
│   ├── SupportsRefund.php          <- Refund capability contract
│   ├── SupportsRecurring.php       <- Recurring capability contract
│   ├── SupportsWebhook.php         <- Webhook capability contract
│   └── SupportsInvoice.php         <- Invoice capability contract
├── Config/GatewayConfig.php        <- Immutable configuration object
├── DTOs/                           <- 8 Data Transfer Objects
│   ├── PaymentRequest.php
│   ├── PaymentResponse.php
│   ├── RefundRequest.php
│   ├── RefundResponse.php
│   ├── WebhookPayload.php
│   ├── Customer.php
│   ├── InvoiceRequest.php
│   └── InvoiceResponse.php
├── Exceptions/                     <- Typed exception hierarchy
│   ├── PaymentException.php
│   ├── AuthenticationException.php
│   ├── ValidationException.php
│   └── ApiException.php
├── Http/HttpClient.php             <- Zero-dependency cURL client
├── Security/
│   ├── SignatureVerifier.php       <- HMAC-SHA256/512, MD5, Bearer token
│   └── PayloadSanitizer.php        <- XSS/injection prevention, card masking
├── Support/
│   ├── Arr.php                     <- Array dot-notation helpers
│   └── Currency.php                <- Currency utility
└── Gateways/
    ├── International/
    │   ├── Stripe/      (6 classes)    <- Gateway, Checkout, Charge, Customer, Refund, Webhook
    │   └── PayPal/      (6 classes)    <- Gateway, Auth, Order, Payment, Refund, Webhook
    ├── MiddleEast/
    │   ├── MyFatoorah/  (5 classes)    <- Gateway, Session, Invoice, Customer, Webhook
    │   ├── Paylink/     (5 classes)    <- Gateway, Auth, Invoice, Reconcile, Webhook
    │   ├── EdfaPay/     (6 classes)    <- Gateway, Checkout, Embedded, ApplePay, Hash, Webhook
    │   ├── Tap/         (7 classes)    <- Gateway, Charge, Authorize, Customer, Invoice, Token, Webhook
    │   ├── ClickPay/    (3 classes)    <- Gateway, Transaction, Webhook
    │   ├── Tamara/      (4 classes)    <- Gateway, Checkout, Order, Webhook
    │   ├── Thawani/     (4 classes)    <- Gateway, Session, Customer, Webhook
    │   ├── Fatora/      (4 classes)    <- Gateway, Checkout, Recurring, Webhook
    │   ├── Payzaty/     (3 classes)    <- Gateway, Checkout, Webhook
    │   ├── Payzah/      (3 classes)    <- Gateway, Payment, Webhook
    │   ├── NeonPay/     (3 classes)    <- Gateway, Payment, Webhook
    │   ├── AsiaPay/     (4 classes)    <- Gateway, Auth, Order, Webhook
    │   └── ZainCash/    (3 classes)    <- Gateway, Transaction, Webhook
    └── Europe/
        ├── Mollie/      (5 classes)    <- Gateway, Payment, Refund, Customer, Webhook
        ├── Redsys/      (3 classes)    <- Gateway, Transaction, Webhook
        └── GoCardless/  (5 classes)    <- Gateway, Payment, Mandate, Refund, Webhook
```

## Design Principles

- **Zero external dependencies** — only PHP 8.1+ with ext-curl, ext-json, ext-mbstring
- **PSR-4 autoloading** — `AzozzALFiras\PaymentGateway\` maps to `src/`
- **Contract-driven** — all gateways implement `GatewayInterface`, optional `SupportsRefund`, `SupportsWebhook`, `SupportsRecurring`
- **Immutable DTOs** — `PaymentRequest`, `PaymentResponse`, etc. use readonly properties
- **Factory pattern** — `PaymentGateway::create()` or typed static methods (`PaymentGateway::stripe()`)
- **Regional organization** — gateways grouped by `International/`, `MiddleEast/`, `Europe/`
