<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Tests\Unit\Gateways\MiddleEast;

use AzozzALFiras\PaymentGateway\PaymentGateway;
use AzozzALFiras\PaymentGateway\Gateways\MiddleEast\Tamara\TamaraGateway;
use AzozzALFiras\PaymentGateway\Contracts\GatewayInterface;
use AzozzALFiras\PaymentGateway\Contracts\SupportsRefund;
use AzozzALFiras\PaymentGateway\Contracts\SupportsWebhook;
use PHPUnit\Framework\TestCase;

class TamaraGatewayTest extends TestCase
{
    private TamaraGateway $gateway;

    protected function setUp(): void
    {
        $this->gateway = PaymentGateway::tamara([
            'api_token' => 'test_token',
            'testMode'  => true,
        ]);
    }

    public function testInstantiation(): void
    {
        $this->assertInstanceOf(TamaraGateway::class, $this->gateway);
        $this->assertInstanceOf(GatewayInterface::class, $this->gateway);
        $this->assertInstanceOf(SupportsRefund::class, $this->gateway);
        $this->assertInstanceOf(SupportsWebhook::class, $this->gateway);
    }

    public function testGetName(): void
    {
        $this->assertEquals('Tamara', $this->gateway->getName());
    }

    public function testIsTestMode(): void
    {
        $this->assertTrue($this->gateway->isTestMode());
    }

    public function testCreateViaFactory(): void
    {
        $gw = PaymentGateway::create('tamara', ['api_token' => 'test', 'testMode' => true]);
        $this->assertInstanceOf(TamaraGateway::class, $gw);
    }

    public function testMissingRequiredConfigThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PaymentGateway::tamara(['testMode' => true]);
    }

    public function testSubModuleAccessors(): void
    {
        $this->assertNotNull($this->gateway->checkouts());
        $this->assertNotNull($this->gateway->orders());
    }
}
