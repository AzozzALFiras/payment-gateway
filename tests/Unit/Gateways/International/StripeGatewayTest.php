<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Tests\Unit\Gateways\International;

use AzozzALFiras\PaymentGateway\PaymentGateway;
use AzozzALFiras\PaymentGateway\Gateways\International\Stripe\StripeGateway;
use AzozzALFiras\PaymentGateway\Contracts\GatewayInterface;
use AzozzALFiras\PaymentGateway\Contracts\SupportsRefund;
use AzozzALFiras\PaymentGateway\Contracts\SupportsWebhook;
use PHPUnit\Framework\TestCase;

class StripeGatewayTest extends TestCase
{
    private StripeGateway $gateway;

    protected function setUp(): void
    {
        $this->gateway = PaymentGateway::stripe([
            'secret_key' => 'sk_test_fake',
            'testMode'   => true,
        ]);
    }

    public function testInstantiation(): void
    {
        $this->assertInstanceOf(StripeGateway::class, $this->gateway);
        $this->assertInstanceOf(GatewayInterface::class, $this->gateway);
        $this->assertInstanceOf(SupportsRefund::class, $this->gateway);
        $this->assertInstanceOf(SupportsWebhook::class, $this->gateway);
    }

    public function testGetName(): void
    {
        $this->assertEquals('Stripe', $this->gateway->getName());
    }

    public function testIsTestMode(): void
    {
        $this->assertTrue($this->gateway->isTestMode());

        $live = PaymentGateway::stripe(['secret_key' => 'sk_live_fake', 'testMode' => false]);
        $this->assertFalse($live->isTestMode());
    }

    public function testCreateViaFactory(): void
    {
        $gw = PaymentGateway::create('stripe', ['secret_key' => 'sk_test_fake', 'testMode' => true]);
        $this->assertInstanceOf(StripeGateway::class, $gw);
    }

    public function testMissingRequiredConfigThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PaymentGateway::stripe(['testMode' => true]);
    }

    public function testSubModuleAccessors(): void
    {
        $this->assertNotNull($this->gateway->checkouts());
        $this->assertNotNull($this->gateway->charges());
        $this->assertNotNull($this->gateway->customers());
        $this->assertNotNull($this->gateway->refunds());
    }
}
