<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Tests\Unit\Gateways\MiddleEast;

use AzozzALFiras\PaymentGateway\PaymentGateway;
use AzozzALFiras\PaymentGateway\Gateways\MiddleEast\Payzaty\PayzatyGateway;
use AzozzALFiras\PaymentGateway\Contracts\GatewayInterface;
use AzozzALFiras\PaymentGateway\Contracts\SupportsWebhook;
use PHPUnit\Framework\TestCase;

class PayzatyGatewayTest extends TestCase
{
    private PayzatyGateway $gateway;

    protected function setUp(): void
    {
        $this->gateway = PaymentGateway::payzaty([
            'account_no' => '12345',
            'secret_key' => 'test_secret',
            'testMode'   => true,
        ]);
    }

    public function testInstantiation(): void
    {
        $this->assertInstanceOf(PayzatyGateway::class, $this->gateway);
        $this->assertInstanceOf(GatewayInterface::class, $this->gateway);
        $this->assertInstanceOf(SupportsWebhook::class, $this->gateway);
    }

    public function testGetName(): void
    {
        $this->assertEquals('Payzaty', $this->gateway->getName());
    }

    public function testIsTestMode(): void
    {
        $this->assertTrue($this->gateway->isTestMode());
    }

    public function testCreateViaFactory(): void
    {
        $gw = PaymentGateway::create('payzaty', [
            'account_no' => '1', 'secret_key' => 'test', 'testMode' => true,
        ]);
        $this->assertInstanceOf(PayzatyGateway::class, $gw);
    }

    public function testMissingRequiredConfigThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PaymentGateway::payzaty(['testMode' => true]);
    }

    public function testSubModuleAccessors(): void
    {
        $this->assertNotNull($this->gateway->checkouts());
    }
}
