<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Tests\Unit\Gateways\MiddleEast;

use AzozzALFiras\PaymentGateway\PaymentGateway;
use AzozzALFiras\PaymentGateway\Gateways\MiddleEast\Thawani\ThawaniGateway;
use AzozzALFiras\PaymentGateway\Contracts\GatewayInterface;
use AzozzALFiras\PaymentGateway\Contracts\SupportsWebhook;
use PHPUnit\Framework\TestCase;

class ThawaniGatewayTest extends TestCase
{
    private ThawaniGateway $gateway;

    protected function setUp(): void
    {
        $this->gateway = PaymentGateway::thawani([
            'secret_key'      => 'test_secret',
            'publishable_key' => 'test_pub',
            'testMode'        => true,
        ]);
    }

    public function testInstantiation(): void
    {
        $this->assertInstanceOf(ThawaniGateway::class, $this->gateway);
        $this->assertInstanceOf(GatewayInterface::class, $this->gateway);
        $this->assertInstanceOf(SupportsWebhook::class, $this->gateway);
    }

    public function testGetName(): void
    {
        $this->assertEquals('Thawani', $this->gateway->getName());
    }

    public function testIsTestMode(): void
    {
        $this->assertTrue($this->gateway->isTestMode());
    }

    public function testCreateViaFactory(): void
    {
        $gw = PaymentGateway::create('thawani', [
            'secret_key' => 'test', 'publishable_key' => 'test', 'testMode' => true,
        ]);
        $this->assertInstanceOf(ThawaniGateway::class, $gw);
    }

    public function testMissingRequiredConfigThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PaymentGateway::thawani(['testMode' => true]);
    }

    public function testSubModuleAccessors(): void
    {
        $this->assertNotNull($this->gateway->sessions());
        $this->assertNotNull($this->gateway->customers());
    }
}
