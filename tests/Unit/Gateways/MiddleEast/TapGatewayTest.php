<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Tests\Unit\Gateways\MiddleEast;

use AzozzALFiras\PaymentGateway\PaymentGateway;
use AzozzALFiras\PaymentGateway\Gateways\MiddleEast\Tap\TapGateway;
use AzozzALFiras\PaymentGateway\Contracts\GatewayInterface;
use AzozzALFiras\PaymentGateway\Contracts\SupportsRefund;
use AzozzALFiras\PaymentGateway\Contracts\SupportsWebhook;
use PHPUnit\Framework\TestCase;

class TapGatewayTest extends TestCase
{
    private TapGateway $gateway;

    protected function setUp(): void
    {
        $this->gateway = PaymentGateway::tap([
            'secret_key' => 'sk_test_fake',
            'testMode'   => true,
        ]);
    }

    public function testInstantiation(): void
    {
        $this->assertInstanceOf(TapGateway::class, $this->gateway);
        $this->assertInstanceOf(GatewayInterface::class, $this->gateway);
        $this->assertInstanceOf(SupportsRefund::class, $this->gateway);
        $this->assertInstanceOf(SupportsWebhook::class, $this->gateway);
    }

    public function testGetName(): void
    {
        $this->assertEquals('Tap', $this->gateway->getName());
    }

    public function testIsTestMode(): void
    {
        $this->assertTrue($this->gateway->isTestMode());
    }

    public function testCreateViaFactory(): void
    {
        $gw = PaymentGateway::create('tap', ['secret_key' => 'test', 'testMode' => true]);
        $this->assertInstanceOf(TapGateway::class, $gw);
    }

    public function testMissingRequiredConfigThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PaymentGateway::tap(['testMode' => true]);
    }

    public function testSubModuleAccessors(): void
    {
        $this->assertNotNull($this->gateway->charges());
        $this->assertNotNull($this->gateway->authorizations());
        $this->assertNotNull($this->gateway->customers());
        $this->assertNotNull($this->gateway->invoices());
        $this->assertNotNull($this->gateway->tokens());
    }
}
