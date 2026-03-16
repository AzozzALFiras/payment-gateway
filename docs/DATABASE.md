# 🗄️ Database Integration Guide

This guide shows how to build a **complete database layer** that works with the Payment Gateway library. Follow these steps to store transactions, manage gateway configs, handle webhooks, and process refunds.

---

## Step 1: Create the Database Tables

Run this SQL to create the 3 required tables.

> **Important:** `type` and `status` columns use `VARCHAR(30)` instead of SQL `ENUM`. This means you can add new statuses in the PHP enum without any database migration.

```sql
-- ═══════════════════════════════════════════════════
-- TABLE 1: Gateway Configurations
-- ═══════════════════════════════════════════════════
CREATE TABLE payment_gateway_configs (
    id                   INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    gateway              VARCHAR(20)  NOT NULL UNIQUE COMMENT 'Matches Gateway enum value',
    display_name         VARCHAR(100) NOT NULL,
    is_active            BOOLEAN      NOT NULL DEFAULT TRUE,
    is_test_mode         BOOLEAN      NOT NULL DEFAULT FALSE,
    credentials          JSON         NOT NULL COMMENT 'Encrypted: api_key, secret_key, etc.',
    supported_currencies JSON         NULL,
    supported_countries  JSON         NULL,
    webhook_secret       VARCHAR(255) NULL,
    settings             JSON         NULL     COMMENT 'region, language, country, etc.',
    created_at           TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ═══════════════════════════════════════════════════
-- TABLE 2: Payment Transactions
-- ═══════════════════════════════════════════════════
CREATE TABLE payment_transactions (
    id                BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    gateway           VARCHAR(20)   NOT NULL COMMENT 'Gateway enum value',
    gateway_txn_id    VARCHAR(255)  NOT NULL COMMENT 'ID from gateway API',
    order_id          VARCHAR(100)  NOT NULL,
    type              VARCHAR(30)   NOT NULL DEFAULT 'sale'    COMMENT 'Cast via TransactionType enum',
    status            VARCHAR(30)   NOT NULL DEFAULT 'pending' COMMENT 'Cast via PaymentStatus enum',
    amount            DECIMAL(15,3) NOT NULL COMMENT '3 decimals for KWD/BHD/OMR',
    currency          CHAR(3)       NOT NULL COMMENT 'ISO 4217',
    refund_id         VARCHAR(255)   NULL,
    refund_amount     DECIMAL(15,3)  NULL,
    parent_txn_id     BIGINT UNSIGNED NULL,
    customer_name     VARCHAR(255)  NULL,
    customer_email    VARCHAR(255)  NULL,
    customer_phone    VARCHAR(50)   NULL,
    payment_url       TEXT          NULL,
    callback_url      TEXT          NULL,
    recurring_token   VARCHAR(255)  NULL,
    gateway_response  JSON          NULL,
    webhook_payload   JSON          NULL,
    metadata          JSON          NULL,
    ip_address        VARCHAR(45)   NULL,
    paid_at           TIMESTAMP     NULL,
    refunded_at       TIMESTAMP     NULL,
    created_at        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_gateway_txn (gateway, gateway_txn_id),
    INDEX idx_order (order_id),
    INDEX idx_status (status),
    INDEX idx_customer (customer_email),
    INDEX idx_created (created_at),
    FOREIGN KEY (parent_txn_id) REFERENCES payment_transactions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ═══════════════════════════════════════════════════
-- TABLE 3: Webhook Logs (audit trail)
-- ═══════════════════════════════════════════════════
CREATE TABLE payment_webhook_logs (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    gateway         VARCHAR(20)  NOT NULL,
    event           VARCHAR(100) NOT NULL,
    transaction_id  VARCHAR(255) NULL,
    is_valid        BOOLEAN      NOT NULL DEFAULT FALSE,
    payload         JSON         NOT NULL,
    headers         JSON         NULL,
    ip_address      VARCHAR(45)  NULL,
    processed_at    TIMESTAMP    NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_gateway_event (gateway, event),
    INDEX idx_txn (transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Step 2: PaymentTransaction Model (Enum Casting)

This model casts `VARCHAR` columns to/from PHP backed enums automatically:

```php
<?php
use AzozzALFiras\PaymentGateway\Enums\Gateway as GatewayEnum;
use AzozzALFiras\PaymentGateway\Enums\PaymentStatus;
use AzozzALFiras\PaymentGateway\Enums\TransactionType;
use AzozzALFiras\PaymentGateway\Enums\Currency;

class PaymentTransaction
{
    public readonly int              $id;
    public readonly GatewayEnum      $gateway;       // 'tap' → Gateway::TAP
    public readonly TransactionType  $type;          // 'sale' → TransactionType::SALE
    public readonly PaymentStatus    $status;        // 'captured' → PaymentStatus::CAPTURED
    public readonly Currency         $currency;      // 'SAR' → Currency::SAR
    public readonly string           $gatewayTxnId;
    public readonly string           $orderId;
    public readonly float            $amount;
    public readonly ?string          $customerEmail;
    public readonly ?string          $paymentUrl;
    public readonly ?string          $recurringToken;

    public static function fromRow(array $row): self
    {
        $model = new self();
        $model->id             = (int) $row['id'];
        $model->gateway        = GatewayEnum::from($row['gateway']);
        $model->type           = TransactionType::from($row['type']);
        $model->status         = PaymentStatus::from($row['status']);
        $model->currency       = Currency::from($row['currency']);
        $model->gatewayTxnId   = $row['gateway_txn_id'];
        $model->orderId        = $row['order_id'];
        $model->amount         = (float) $row['amount'];
        $model->customerEmail  = $row['customer_email'];
        $model->paymentUrl     = $row['payment_url'];
        $model->recurringToken = $row['recurring_token'];
        return $model;
    }

    public function toDbArray(): array
    {
        return [
            'gateway'        => $this->gateway->value,
            'type'           => $this->type->value,
            'status'         => $this->status->value,
            'currency'       => $this->currency->value,
            'gateway_txn_id' => $this->gatewayTxnId,
            'order_id'       => $this->orderId,
            'amount'         => $this->amount,
        ];
    }

    public function isPaid(): bool       { return $this->status->isSuccessful(); }
    public function canRefund(): bool     { return $this->status->isRefundable() && $this->gateway->supportsRefund(); }
    public function isFinal(): bool       { return $this->status->isFinal(); }
    public function formatted(): string   { return $this->currency->format($this->amount); }
}

// Laravel? Use built-in casts:
// protected $casts = [
//     'gateway'  => GatewayEnum::class,
//     'type'     => TransactionType::class,
//     'status'   => PaymentStatus::class,
//     'currency' => Currency::class,
// ];
```

---

## Step 3: PaymentService

```php
<?php
use AzozzALFiras\PaymentGateway\PaymentGateway;
use AzozzALFiras\PaymentGateway\Enums\PaymentStatus;
use AzozzALFiras\PaymentGateway\Enums\TransactionType;
use AzozzALFiras\PaymentGateway\DTOs\{PaymentRequest, RefundRequest, Customer};
use AzozzALFiras\PaymentGateway\Security\PayloadSanitizer;

class PaymentService
{
    public function __construct(private PDO $db) {}

    public function loadGateway(string $name): \AzozzALFiras\PaymentGateway\Contracts\GatewayInterface
    {
        $stmt = $this->db->prepare("SELECT * FROM payment_gateway_configs WHERE gateway = ? AND is_active = 1");
        $stmt->execute([$name]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new \RuntimeException("Gateway '{$name}' not found or inactive");

        $creds = json_decode($row['credentials'], true);
        $settings = json_decode($row['settings'] ?? '{}', true);
        return PaymentGateway::create($row['gateway'], array_merge($creds, $settings, ['testMode' => (bool) $row['is_test_mode']]));
    }

    public function createPayment(string $gatewayName, float $amount, string $currency, string $orderId, string $callbackUrl, array $customer = []): array
    {
        $gateway = $this->loadGateway($gatewayName);

        $response = $gateway->purchase(new PaymentRequest(
            amount: PayloadSanitizer::amount($amount), currency: $currency,
            orderId: $orderId, callbackUrl: PayloadSanitizer::url($callbackUrl),
            customer: !empty($customer) ? new Customer(
                name: PayloadSanitizer::string($customer['name'] ?? ''),
                email: PayloadSanitizer::email($customer['email'] ?? ''),
            ) : null,
        ));

        $this->db->prepare("INSERT INTO payment_transactions (gateway, gateway_txn_id, order_id, type, status, amount, currency, payment_url, callback_url, gateway_response, ip_address) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([$gatewayName, $response->transactionId, $orderId, TransactionType::SALE->value, PaymentStatus::normalize($response->status)->value, $response->amount, $response->currency, $response->paymentUrl, $callbackUrl, json_encode($response->rawResponse), $_SERVER['REMOTE_ADDR'] ?? null]);

        return ['db_id' => $this->db->lastInsertId(), 'success' => $response->success, 'payment_url' => $response->paymentUrl];
    }

    public function refund(int $txnId, float $amount, string $reason = 'Refund'): array
    {
        $stmt = $this->db->prepare("SELECT * FROM payment_transactions WHERE id = ?");
        $stmt->execute([$txnId]);
        $txn = $stmt->fetch(PDO::FETCH_ASSOC);

        $gateway = $this->loadGateway($txn['gateway']);
        $result = $gateway->refund(new RefundRequest(transactionId: $txn['gateway_txn_id'], amount: $amount, currency: $txn['currency'], reason: $reason));

        $this->db->prepare("UPDATE payment_transactions SET status = ?, refunded_at = NOW() WHERE id = ?")
            ->execute([$amount < (float) $txn['amount'] ? 'partial_refund' : 'refunded', $txnId]);

        return ['success' => $result->success, 'refund_id' => $result->refundId];
    }
}
```

---

## Step 4: Webhook Controller

```php
<?php
class WebhookController
{
    public function __construct(private PaymentService $service, private PDO $db) {}

    public function handle(string $gatewayName): void
    {
        $payload = json_decode(file_get_contents('php://input'), true) ?? [];
        $headers = getallheaders();
        $gateway = $this->service->loadGateway($gatewayName);
        $webhook = $gateway->handleWebhook($payload, $headers);

        // Log
        $this->db->prepare("INSERT INTO payment_webhook_logs (gateway, event, transaction_id, is_valid, payload, headers, ip_address) VALUES (?,?,?,?,?,?,?)")
            ->execute([$gatewayName, $webhook->event, $webhook->transactionId, $webhook->isValid ? 1 : 0, json_encode($webhook->rawPayload), json_encode($headers), $_SERVER['REMOTE_ADDR'] ?? null]);

        if (!$webhook->isValid) { http_response_code(401); return; }

        // Update transaction
        $status = PaymentStatus::normalize($webhook->status);
        $this->db->prepare("UPDATE payment_transactions SET status = ?, webhook_payload = ?, paid_at = IF(? IN ('captured','paid'), NOW(), paid_at) WHERE gateway = ? AND gateway_txn_id = ?")
            ->execute([$status->value, json_encode($webhook->rawPayload), $status->value, $gatewayName, $webhook->transactionId]);

        http_response_code(200);
    }
}
```

---

## Step 5: Usage

```php
$db = new PDO('mysql:host=localhost;dbname=myapp', 'root', '');
$service = new PaymentService($db);

// Pay via any gateway — same code
$result = $service->createPayment('tap', 150.00, 'SAR', 'ORD-001', 'https://yoursite.com/webhook/tap', ['name' => 'Ahmed', 'email' => 'ahmed@example.com']);
header("Location: {$result['payment_url']}");

// Refund
$service->refund(42, 50.00, 'Customer request');

// Load transaction with enum casting
$txn = PaymentTransaction::fromRow($db->query("SELECT * FROM payment_transactions WHERE id = 42")->fetch());
echo $txn->gateway->label();       // "Tap Payments"
echo $txn->canRefund();            // true
echo $txn->formatted();            // "150.50 SAR"
```
