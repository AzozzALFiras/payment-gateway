<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Tests\Unit\Gateways\MiddleEast;

use AzozzALFiras\PaymentGateway\PaymentGateway;
use AzozzALFiras\PaymentGateway\Gateways\MiddleEast\ZainCash\ZainCashGateway;
use AzozzALFiras\PaymentGateway\Contracts\GatewayInterface;
use AzozzALFiras\PaymentGateway\Contracts\SupportsWebhook;
use PHPUnit\Framework\TestCase;

class ZainCashGatewayTest extends TestCase
{
    private ZainCashGateway $gateway;

    protected function setUp(): void
    {
        $this->gateway = PaymentGateway::zaincash([
            'secret'      => 'test_secret',
            'msisdn'      => '9647835077893',
            'merchant_id' => '5ffacf6612b5777c6d44266f',
            'testMode'    => true,
        ]);
    }

    public function testInstantiation(): void
    {
        $this->assertInstanceOf(ZainCashGateway::class, $this->gateway);
        $this->assertInstanceOf(GatewayInterface::class, $this->gateway);
        $this->assertInstanceOf(SupportsWebhook::class, $this->gateway);
    }

    public function testGetName(): void
    {
        $this->assertEquals('ZainCash', $this->gateway->getName());
    }

    public function testIsTestMode(): void
    {
        $this->assertTrue($this->gateway->isTestMode());
    }

    public function testCreateViaFactory(): void
    {
        $gw = PaymentGateway::create('zaincash', [
            'secret' => 't', 'msisdn' => 't', 'merchant_id' => 't', 'testMode' => true,
        ]);
        $this->assertInstanceOf(ZainCashGateway::class, $gw);
    }

    public function testSubModuleAccessors(): void
    {
        $this->assertNotNull($this->gateway->transactions());
    }
}
