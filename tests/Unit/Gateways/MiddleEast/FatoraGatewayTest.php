<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Tests\Unit\Gateways\MiddleEast;

use AzozzALFiras\PaymentGateway\PaymentGateway;
use AzozzALFiras\PaymentGateway\Gateways\MiddleEast\Fatora\FatoraGateway;
use AzozzALFiras\PaymentGateway\Contracts\GatewayInterface;
use AzozzALFiras\PaymentGateway\Contracts\SupportsRefund;
use AzozzALFiras\PaymentGateway\Contracts\SupportsRecurring;
use AzozzALFiras\PaymentGateway\Contracts\SupportsWebhook;
use PHPUnit\Framework\TestCase;

class FatoraGatewayTest extends TestCase
{
    private FatoraGateway $gateway;

    protected function setUp(): void
    {
        $this->gateway = PaymentGateway::fatora([
            'api_key'  => 'test_api_key',
            'testMode' => true,
        ]);
    }

    public function testInstantiation(): void
    {
        $this->assertInstanceOf(FatoraGateway::class, $this->gateway);
        $this->assertInstanceOf(GatewayInterface::class, $this->gateway);
        $this->assertInstanceOf(SupportsRefund::class, $this->gateway);
        $this->assertInstanceOf(SupportsRecurring::class, $this->gateway);
        $this->assertInstanceOf(SupportsWebhook::class, $this->gateway);
    }

    public function testGetName(): void
    {
        $this->assertEquals('Fatora', $this->gateway->getName());
    }

    public function testIsTestMode(): void
    {
        $this->assertTrue($this->gateway->isTestMode());
    }

    public function testCreateViaFactory(): void
    {
        $gw = PaymentGateway::create('fatora', ['api_key' => 'test', 'testMode' => true]);
        $this->assertInstanceOf(FatoraGateway::class, $gw);
    }

    public function testMissingRequiredConfigThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PaymentGateway::fatora(['testMode' => true]);
    }

    public function testSubModuleAccessors(): void
    {
        $this->assertNotNull($this->gateway->checkouts());
        $this->assertNotNull($this->gateway->recurringPayments());
    }
}
