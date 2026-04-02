<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Tests\Unit\Gateways\Europe;

use AzozzALFiras\PaymentGateway\PaymentGateway;
use AzozzALFiras\PaymentGateway\Gateways\Europe\Redsys\RedsysGateway;
use AzozzALFiras\PaymentGateway\Contracts\GatewayInterface;
use AzozzALFiras\PaymentGateway\Contracts\SupportsRefund;
use AzozzALFiras\PaymentGateway\Contracts\SupportsWebhook;
use PHPUnit\Framework\TestCase;

class RedsysGatewayTest extends TestCase
{
    private RedsysGateway $gateway;

    protected function setUp(): void
    {
        $this->gateway = PaymentGateway::redsys([
            'merchant_code' => '999008881',
            'merchant_key'  => base64_encode('test_key_1234567'),
            'terminal'      => '1',
            'testMode'      => true,
        ]);
    }

    public function testInstantiation(): void
    {
        $this->assertInstanceOf(RedsysGateway::class, $this->gateway);
        $this->assertInstanceOf(GatewayInterface::class, $this->gateway);
        $this->assertInstanceOf(SupportsRefund::class, $this->gateway);
        $this->assertInstanceOf(SupportsWebhook::class, $this->gateway);
    }

    public function testGetName(): void
    {
        $this->assertEquals('Redsys', $this->gateway->getName());
    }

    public function testIsTestMode(): void
    {
        $this->assertTrue($this->gateway->isTestMode());
    }

    public function testCreateViaFactory(): void
    {
        $gw = PaymentGateway::create('redsys', [
            'merchant_code' => '999', 'merchant_key' => base64_encode('key'), 'testMode' => true,
        ]);
        $this->assertInstanceOf(RedsysGateway::class, $gw);
    }

    public function testSubModuleAccessors(): void
    {
        $this->assertNotNull($this->gateway->transactions());
    }
}
