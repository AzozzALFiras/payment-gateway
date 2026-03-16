<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Tests\Unit;

use AzozzALFiras\PaymentGateway\Gateways\EdfaPay\EdfaPayHash;
use PHPUnit\Framework\TestCase;

class EdfaPayHashTest extends TestCase
{
    public function testGenerateHash(): void
    {
        $hash = EdfaPayHash::generate(
            email:      'test@example.com',
            cardNumber: '5123456789012346',
            password:   'SECRET_PASSWORD',
        );

        // Hash should be a 32-char MD5 hex string
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $hash);
    }

    public function testGenerateFromMasked(): void
    {
        $hash = EdfaPayHash::generateFromMasked(
            email:    'test@example.com',
            first6:   '512345',
            last4:    '2346',
            password: 'SECRET_PASSWORD',
        );

        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $hash);
    }

    public function testHashConsistency(): void
    {
        $hash1 = EdfaPayHash::generate('user@test.com', '4111111111111111', 'pass123');
        $hash2 = EdfaPayHash::generate('user@test.com', '4111111111111111', 'pass123');

        // Same inputs should produce the same hash
        $this->assertEquals($hash1, $hash2);
    }

    public function testHashDiffersWithDifferentInputs(): void
    {
        $hash1 = EdfaPayHash::generate('user1@test.com', '4111111111111111', 'pass123');
        $hash2 = EdfaPayHash::generate('user2@test.com', '4111111111111111', 'pass123');

        $this->assertNotEquals($hash1, $hash2);
    }

    public function testGenerateMatchesFromMasked(): void
    {
        $cardNumber = '5123456789012346';
        $first6 = '512345';
        $last4 = '2346';
        $email = 'test@example.com';
        $password = 'TEST_PASS';

        $hashFull = EdfaPayHash::generate($email, $cardNumber, $password);
        $hashMasked = EdfaPayHash::generateFromMasked($email, $first6, $last4, $password);

        // Both methods should produce the same hash
        $this->assertEquals($hashFull, $hashMasked);
    }

    public function testHashFormula(): void
    {
        // Manual calculation to verify the formula
        $email = 'user@test.com';
        $cardNumber = '4111111111111111';
        $password = 'mypassword';

        $reversed_email = strrev($email);
        $first6 = '411111';
        $last4 = '1111';
        $reversed_pan = strrev($first6 . $last4);
        $base = $reversed_email . $password . $reversed_pan;
        $expected = md5(strtoupper($base));

        $actual = EdfaPayHash::generate($email, $cardNumber, $password);

        $this->assertEquals($expected, $actual);
    }
}
