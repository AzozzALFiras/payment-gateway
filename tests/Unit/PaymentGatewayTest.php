<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Tests\Unit;

use AzozzALFiras\PaymentGateway\PaymentGateway;
use AzozzALFiras\PaymentGateway\Gateways\MyFatoorah\MyFatoorahGateway;
use AzozzALFiras\PaymentGateway\Gateways\Paylink\PaylinkGateway;
use AzozzALFiras\PaymentGateway\Gateways\EdfaPay\EdfaPayGateway;
use PHPUnit\Framework\TestCase;

class PaymentGatewayTest extends TestCase
{
    public function testCreateMyFatoorahGateway(): void
    {
        $gateway = PaymentGateway::create('myfatoorah', [
            'api_key'  => 'test_key',
            'testMode' => true,
        ]);

        $this->assertInstanceOf(MyFatoorahGateway::class, $gateway);
        $this->assertEquals('MyFatoorah', $gateway->getName());
        $this->assertTrue($gateway->isTestMode());
    }

    public function testCreatePaylinkGateway(): void
    {
        $gateway = PaymentGateway::create('paylink', [
            'api_id'     => 'test_id',
            'secret_key' => 'test_secret',
            'testMode'   => true,
        ]);

        $this->assertInstanceOf(PaylinkGateway::class, $gateway);
        $this->assertEquals('Paylink', $gateway->getName());
        $this->assertTrue($gateway->isTestMode());
    }

    public function testCreateEdfaPayGateway(): void
    {
        $gateway = PaymentGateway::create('edfapay', [
            'client_key' => 'test_key',
            'password'   => 'test_password',
            'testMode'   => true,
        ]);

        $this->assertInstanceOf(EdfaPayGateway::class, $gateway);
        $this->assertEquals('EdfaPay', $gateway->getName());
        $this->assertTrue($gateway->isTestMode());
    }

    public function testStaticFactoryMethods(): void
    {
        $mf = PaymentGateway::myfatoorah(['api_key' => 'test']);
        $this->assertInstanceOf(MyFatoorahGateway::class, $mf);

        $pl = PaymentGateway::paylink(['api_id' => 'test', 'secret_key' => 'test']);
        $this->assertInstanceOf(PaylinkGateway::class, $pl);

        $ep = PaymentGateway::edfapay(['client_key' => 'test', 'password' => 'test']);
        $this->assertInstanceOf(EdfaPayGateway::class, $ep);
    }

    public function testUnsupportedDriverThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported payment gateway driver');

        PaymentGateway::create('stripe', []);
    }

    public function testGetAvailableDrivers(): void
    {
        $drivers = PaymentGateway::getAvailableDrivers();

        $this->assertContains('myfatoorah', $drivers);
        $this->assertContains('paylink', $drivers);
        $this->assertContains('edfapay', $drivers);
        $this->assertCount(3, $drivers);
    }

    public function testCaseInsensitiveDriverName(): void
    {
        $g1 = PaymentGateway::create('MyFatoorah', ['api_key' => 'test']);
        $g2 = PaymentGateway::create('EDFAPAY', ['client_key' => 'test', 'password' => 'test']);

        $this->assertInstanceOf(MyFatoorahGateway::class, $g1);
        $this->assertInstanceOf(EdfaPayGateway::class, $g2);
    }
}
