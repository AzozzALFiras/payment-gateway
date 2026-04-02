<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Tests\Unit\Gateways\Europe;

use AzozzALFiras\PaymentGateway\PaymentGateway;
use AzozzALFiras\PaymentGateway\Gateways\Europe\GoCardless\GoCardlessGateway;
use AzozzALFiras\PaymentGateway\Contracts\GatewayInterface;
use AzozzALFiras\PaymentGateway\Contracts\SupportsRefund;
use AzozzALFiras\PaymentGateway\Contracts\SupportsWebhook;
use PHPUnit\Framework\TestCase;

class GoCardlessGatewayTest extends TestCase
{
    private GoCardlessGateway $gateway;

    protected function setUp(): void
    {
        $this->gateway = PaymentGateway::gocardless([
            'access_token' => 'sandbox_test_token',
            'testMode'     => true,
        ]);
    }

    public function testInstantiation(): void
    {
        $this->assertInstanceOf(GoCardlessGateway::class, $this->gateway);
        $this->assertInstanceOf(GatewayInterface::class, $this->gateway);
        $this->assertInstanceOf(SupportsRefund::class, $this->gateway);
        $this->assertInstanceOf(SupportsWebhook::class, $this->gateway);
    }

    public function testGetName(): void
    {
        $this->assertEquals('GoCardless', $this->gateway->getName());
    }

    public function testIsTestMode(): void
    {
        $this->assertTrue($this->gateway->isTestMode());
    }

    public function testCreateViaFactory(): void
    {
        $gw = PaymentGateway::create('gocardless', ['access_token' => 'test', 'testMode' => true]);
        $this->assertInstanceOf(GoCardlessGateway::class, $gw);
    }

    public function testMissingRequiredConfigThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PaymentGateway::gocardless(['testMode' => true]);
    }

    public function testSubModuleAccessors(): void
    {
        $this->assertNotNull($this->gateway->payments());
        $this->assertNotNull($this->gateway->mandates());
        $this->assertNotNull($this->gateway->refunds());
    }
}
