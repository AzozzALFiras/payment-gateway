<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Tests\Unit\Gateways\International;

use AzozzALFiras\PaymentGateway\PaymentGateway;
use AzozzALFiras\PaymentGateway\Gateways\International\PayPal\PayPalGateway;
use AzozzALFiras\PaymentGateway\Contracts\GatewayInterface;
use AzozzALFiras\PaymentGateway\Contracts\SupportsRefund;
use AzozzALFiras\PaymentGateway\Contracts\SupportsWebhook;
use PHPUnit\Framework\TestCase;

class PayPalGatewayTest extends TestCase
{
    private PayPalGateway $gateway;

    protected function setUp(): void
    {
        $this->gateway = PaymentGateway::paypal([
            'client_id'     => 'test_client_id',
            'client_secret' => 'test_client_secret',
            'testMode'      => true,
        ]);
    }

    public function testInstantiation(): void
    {
        $this->assertInstanceOf(PayPalGateway::class, $this->gateway);
        $this->assertInstanceOf(GatewayInterface::class, $this->gateway);
        $this->assertInstanceOf(SupportsRefund::class, $this->gateway);
        $this->assertInstanceOf(SupportsWebhook::class, $this->gateway);
    }

    public function testGetName(): void
    {
        $this->assertEquals('PayPal', $this->gateway->getName());
    }

    public function testIsTestMode(): void
    {
        $this->assertTrue($this->gateway->isTestMode());
    }

    public function testCreateViaFactory(): void
    {
        $gw = PaymentGateway::create('paypal', [
            'client_id' => 'test', 'client_secret' => 'test', 'testMode' => true,
        ]);
        $this->assertInstanceOf(PayPalGateway::class, $gw);
    }

    public function testMissingClientIdThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PaymentGateway::paypal(['client_secret' => 'test', 'testMode' => true]);
    }

    public function testMissingClientSecretThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PaymentGateway::paypal(['client_id' => 'test', 'testMode' => true]);
    }

    public function testSubModuleAccessors(): void
    {
        $this->assertNotNull($this->gateway->orders());
        $this->assertNotNull($this->gateway->payments());
        $this->assertNotNull($this->gateway->refunds());
    }
}
