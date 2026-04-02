<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Tests\Unit;

use AzozzALFiras\PaymentGateway\Enums\Gateway;
use PHPUnit\Framework\TestCase;

class GatewayEnumTest extends TestCase
{
    public function testAllCasesCount(): void
    {
        $this->assertCount(18, Gateway::cases());
    }

    public function testValues(): void
    {
        $values = Gateway::values();
        $this->assertContains('stripe', $values);
        $this->assertContains('paypal', $values);
        $this->assertContains('myfatoorah', $values);
        $this->assertContains('mollie', $values);
        $this->assertContains('redsys', $values);
        $this->assertContains('gocardless', $values);
        $this->assertContains('asiapay', $values);
        $this->assertContains('zaincash', $values);
    }

    public function testLabels(): void
    {
        $this->assertEquals('Stripe', Gateway::STRIPE->label());
        $this->assertEquals('PayPal', Gateway::PAYPAL->label());
        $this->assertEquals('MyFatoorah', Gateway::MYFATOORAH->label());
        $this->assertEquals('Tap Payments', Gateway::TAP->label());
        $this->assertEquals('Mollie', Gateway::MOLLIE->label());
        $this->assertEquals('Redsys', Gateway::REDSYS->label());
        $this->assertEquals('GoCardless', Gateway::GOCARDLESS->label());
        $this->assertEquals('AsiaPay', Gateway::ASIAPAY->label());
        $this->assertEquals('ZainCash', Gateway::ZAINCASH->label());
    }

    public function testRegions(): void
    {
        $this->assertEquals('International', Gateway::STRIPE->region());
        $this->assertEquals('International', Gateway::PAYPAL->region());
        $this->assertEquals('MiddleEast', Gateway::MYFATOORAH->region());
        $this->assertEquals('MiddleEast', Gateway::TAP->region());
        $this->assertEquals('MiddleEast', Gateway::ASIAPAY->region());
        $this->assertEquals('MiddleEast', Gateway::ZAINCASH->region());
        $this->assertEquals('Europe', Gateway::MOLLIE->region());
        $this->assertEquals('Europe', Gateway::REDSYS->region());
        $this->assertEquals('Europe', Gateway::GOCARDLESS->region());
    }

    public function testCountries(): void
    {
        $this->assertContains('USA', Gateway::STRIPE->countries());
        $this->assertContains('SAU', Gateway::MYFATOORAH->countries());
        $this->assertContains('IRQ', Gateway::ASIAPAY->countries());
        $this->assertContains('IRQ', Gateway::ZAINCASH->countries());
        $this->assertContains('NLD', Gateway::MOLLIE->countries());
        $this->assertContains('ESP', Gateway::REDSYS->countries());
        $this->assertContains('GBR', Gateway::GOCARDLESS->countries());
    }

    public function testCurrencies(): void
    {
        $this->assertContains('USD', Gateway::STRIPE->currencies());
        $this->assertContains('IQD', Gateway::ASIAPAY->currencies());
        $this->assertContains('IQD', Gateway::ZAINCASH->currencies());
        $this->assertContains('EUR', Gateway::MOLLIE->currencies());
        $this->assertContains('EUR', Gateway::REDSYS->currencies());
        $this->assertContains('GBP', Gateway::GOCARDLESS->currencies());
    }

    public function testSupportsRefund(): void
    {
        $this->assertTrue(Gateway::STRIPE->supportsRefund());
        $this->assertTrue(Gateway::PAYPAL->supportsRefund());
        $this->assertTrue(Gateway::MOLLIE->supportsRefund());
        $this->assertTrue(Gateway::REDSYS->supportsRefund());
        $this->assertTrue(Gateway::GOCARDLESS->supportsRefund());
        $this->assertFalse(Gateway::THAWANI->supportsRefund());
        $this->assertFalse(Gateway::PAYZAH->supportsRefund());
        $this->assertFalse(Gateway::ZAINCASH->supportsRefund());
    }

    public function testSupportsRecurring(): void
    {
        $this->assertTrue(Gateway::EDFAPAY->supportsRecurring());
        $this->assertTrue(Gateway::FATORA->supportsRecurring());
        $this->assertFalse(Gateway::STRIPE->supportsRecurring());
    }

    public function testIsBNPL(): void
    {
        $this->assertTrue(Gateway::TAMARA->isBNPL());
        $this->assertFalse(Gateway::STRIPE->isBNPL());
    }

    public function testFromName(): void
    {
        $this->assertEquals(Gateway::STRIPE, Gateway::fromName('stripe'));
        $this->assertEquals(Gateway::MYFATOORAH, Gateway::fromName('MyFatoorah'));
        $this->assertEquals(Gateway::MOLLIE, Gateway::fromName('MOLLIE'));
    }

    public function testFromNameInvalidThrows(): void
    {
        $this->expectException(\ValueError::class);
        Gateway::fromName('nonexistent');
    }
}
