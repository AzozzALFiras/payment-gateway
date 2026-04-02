<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Tests\Unit\Gateways\MiddleEast;

use AzozzALFiras\PaymentGateway\PaymentGateway;
use AzozzALFiras\PaymentGateway\Gateways\MiddleEast\EdfaPay\EdfaPayGateway;
use AzozzALFiras\PaymentGateway\Contracts\GatewayInterface;
use AzozzALFiras\PaymentGateway\Contracts\SupportsRefund;
use AzozzALFiras\PaymentGateway\Contracts\SupportsRecurring;
use AzozzALFiras\PaymentGateway\Contracts\SupportsWebhook;
use PHPUnit\Framework\TestCase;

class EdfaPayGatewayTest extends TestCase
{
    private EdfaPayGateway $gateway;

    protected function setUp(): void
    {
        $this->gateway = PaymentGateway::edfapay([
            'client_key' => 'test_key',
            'password'   => 'test_pass',
            'testMode'   => true,
        ]);
    }

    public function testInstantiation(): void
    {
        $this->assertInstanceOf(EdfaPayGateway::class, $this->gateway);
        $this->assertInstanceOf(GatewayInterface::class, $this->gateway);
        $this->assertInstanceOf(SupportsRefund::class, $this->gateway);
        $this->assertInstanceOf(SupportsRecurring::class, $this->gateway);
        $this->assertInstanceOf(SupportsWebhook::class, $this->gateway);
    }

    public function testGetName(): void
    {
        $this->assertEquals('EdfaPay', $this->gateway->getName());
    }

    public function testIsTestMode(): void
    {
        $this->assertTrue($this->gateway->isTestMode());
    }

    public function testCreateViaFactory(): void
    {
        $gw = PaymentGateway::create('edfapay', ['client_key' => 'test', 'password' => 'test', 'testMode' => true]);
        $this->assertInstanceOf(EdfaPayGateway::class, $gw);
    }

    public function testMissingRequiredConfigThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PaymentGateway::edfapay(['testMode' => true]);
    }

    public function testSubModuleAccessors(): void
    {
        $this->assertNotNull($this->gateway->checkout());
        $this->assertNotNull($this->gateway->embedded());
        $this->assertNotNull($this->gateway->applePay());
    }
}
