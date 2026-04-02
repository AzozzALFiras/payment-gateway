<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Tests\Unit\Gateways\MiddleEast;

use AzozzALFiras\PaymentGateway\PaymentGateway;
use AzozzALFiras\PaymentGateway\Gateways\MiddleEast\MyFatoorah\MyFatoorahGateway;
use AzozzALFiras\PaymentGateway\Contracts\GatewayInterface;
use AzozzALFiras\PaymentGateway\Contracts\SupportsRefund;
use AzozzALFiras\PaymentGateway\Contracts\SupportsWebhook;
use PHPUnit\Framework\TestCase;

class MyFatoorahGatewayTest extends TestCase
{
    private MyFatoorahGateway $gateway;

    protected function setUp(): void
    {
        $this->gateway = PaymentGateway::myfatoorah([
            'api_key'  => 'test_api_key',
            'testMode' => true,
        ]);
    }

    public function testInstantiation(): void
    {
        $this->assertInstanceOf(MyFatoorahGateway::class, $this->gateway);
        $this->assertInstanceOf(GatewayInterface::class, $this->gateway);
        $this->assertInstanceOf(SupportsRefund::class, $this->gateway);
        $this->assertInstanceOf(SupportsWebhook::class, $this->gateway);
    }

    public function testGetName(): void
    {
        $this->assertEquals('MyFatoorah', $this->gateway->getName());
    }

    public function testIsTestMode(): void
    {
        $this->assertTrue($this->gateway->isTestMode());
    }

    public function testCreateViaFactory(): void
    {
        $gw = PaymentGateway::create('myfatoorah', ['api_key' => 'test', 'testMode' => true]);
        $this->assertInstanceOf(MyFatoorahGateway::class, $gw);
    }

    public function testMissingRequiredConfigThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PaymentGateway::myfatoorah(['testMode' => true]);
    }

    public function testSubModuleAccessors(): void
    {
        $this->assertNotNull($this->gateway->sessions());
        $this->assertNotNull($this->gateway->customers());
    }

    public function testCountryDefaults(): void
    {
        $this->assertNotEmpty($this->gateway->getBaseUrl());
        $this->assertNotEmpty($this->gateway->getCountry());
        $this->assertNotEmpty($this->gateway->getDefaultCurrency());
    }
}
