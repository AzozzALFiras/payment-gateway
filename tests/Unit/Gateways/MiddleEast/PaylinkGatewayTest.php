<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Tests\Unit\Gateways\MiddleEast;

use AzozzALFiras\PaymentGateway\PaymentGateway;
use AzozzALFiras\PaymentGateway\Gateways\MiddleEast\Paylink\PaylinkGateway;
use AzozzALFiras\PaymentGateway\Contracts\GatewayInterface;
use AzozzALFiras\PaymentGateway\Contracts\SupportsWebhook;
use PHPUnit\Framework\TestCase;

class PaylinkGatewayTest extends TestCase
{
    private PaylinkGateway $gateway;

    protected function setUp(): void
    {
        $this->gateway = PaymentGateway::paylink([
            'api_id'     => 'test_id',
            'secret_key' => 'test_secret',
            'testMode'   => true,
        ]);
    }

    public function testInstantiation(): void
    {
        $this->assertInstanceOf(PaylinkGateway::class, $this->gateway);
        $this->assertInstanceOf(GatewayInterface::class, $this->gateway);
        $this->assertInstanceOf(SupportsWebhook::class, $this->gateway);
    }

    public function testGetName(): void
    {
        $this->assertEquals('Paylink', $this->gateway->getName());
    }

    public function testIsTestMode(): void
    {
        $this->assertTrue($this->gateway->isTestMode());
    }

    public function testCreateViaFactory(): void
    {
        $gw = PaymentGateway::create('paylink', ['api_id' => 'test', 'secret_key' => 'test', 'testMode' => true]);
        $this->assertInstanceOf(PaylinkGateway::class, $gw);
    }

    public function testMissingRequiredConfigThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PaymentGateway::paylink(['testMode' => true]);
    }

    public function testSubModuleAccessors(): void
    {
        $this->assertNotNull($this->gateway->reconcile());
    }
}
