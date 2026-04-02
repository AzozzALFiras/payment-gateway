<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Tests\Unit\Gateways\MiddleEast;

use AzozzALFiras\PaymentGateway\PaymentGateway;
use AzozzALFiras\PaymentGateway\Gateways\MiddleEast\AsiaPay\AsiaPayGateway;
use AzozzALFiras\PaymentGateway\Contracts\GatewayInterface;
use AzozzALFiras\PaymentGateway\Contracts\SupportsRefund;
use AzozzALFiras\PaymentGateway\Contracts\SupportsWebhook;
use PHPUnit\Framework\TestCase;

class AsiaPayGatewayTest extends TestCase
{
    private AsiaPayGateway $gateway;

    protected function setUp(): void
    {
        $this->gateway = PaymentGateway::asiapay([
            'app_key'     => 'test_app_key',
            'app_secret'  => 'test_app_secret',
            'private_key' => 'test_private_key',
            'app_id'      => 'test_app_id',
            'merch_code'  => 'test_merch',
            'testMode'    => true,
        ]);
    }

    public function testInstantiation(): void
    {
        $this->assertInstanceOf(AsiaPayGateway::class, $this->gateway);
        $this->assertInstanceOf(GatewayInterface::class, $this->gateway);
        $this->assertInstanceOf(SupportsRefund::class, $this->gateway);
        $this->assertInstanceOf(SupportsWebhook::class, $this->gateway);
    }

    public function testGetName(): void
    {
        $this->assertEquals('AsiaPay', $this->gateway->getName());
    }

    public function testIsTestMode(): void
    {
        $this->assertTrue($this->gateway->isTestMode());
    }

    public function testCreateViaFactory(): void
    {
        $gw = PaymentGateway::create('asiapay', [
            'app_key' => 't', 'app_secret' => 't', 'private_key' => 't',
            'app_id' => 't', 'merch_code' => 't', 'testMode' => true,
        ]);
        $this->assertInstanceOf(AsiaPayGateway::class, $gw);
    }

    public function testMissingRequiredConfigThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PaymentGateway::asiapay(['testMode' => true]);
    }

    public function testSubModuleAccessors(): void
    {
        $this->assertNotNull($this->gateway->orders());
    }
}
