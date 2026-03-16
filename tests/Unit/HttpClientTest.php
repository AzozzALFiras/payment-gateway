<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Tests\Unit;

use AzozzALFiras\PaymentGateway\Http\HttpClient;
use PHPUnit\Framework\TestCase;

class HttpClientTest extends TestCase
{
    private HttpClient $client;

    protected function setUp(): void
    {
        $this->client = new HttpClient(timeout: 10);
    }

    public function testSetHeader(): void
    {
        $result = $this->client->setHeader('X-Custom', 'value');
        $this->assertInstanceOf(HttpClient::class, $result);
    }

    public function testSetBearerToken(): void
    {
        $result = $this->client->setBearerToken('test_token_123');
        $this->assertInstanceOf(HttpClient::class, $result);
    }

    public function testFluentInterface(): void
    {
        // Test method chaining
        $client = (new HttpClient())
            ->setHeader('Accept', 'application/json')
            ->setBearerToken('token');

        $this->assertInstanceOf(HttpClient::class, $client);
    }
}
