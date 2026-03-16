<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway;

use AzozzALFiras\PaymentGateway\Config\GatewayConfig;
use AzozzALFiras\PaymentGateway\Contracts\GatewayInterface;
use AzozzALFiras\PaymentGateway\Gateways\MyFatoorah\MyFatoorahGateway;
use AzozzALFiras\PaymentGateway\Gateways\Paylink\PaylinkGateway;
use AzozzALFiras\PaymentGateway\Gateways\EdfaPay\EdfaPayGateway;

/**
 * Payment Gateway Factory — the single entry point for creating gateway instances.
 *
 * Usage:
 *   $gateway = PaymentGateway::create('myfatoorah', ['api_key' => '...']);
 *   $gateway = PaymentGateway::myfatoorah(['api_key' => '...']);
 *   $gateway = PaymentGateway::paylink(['api_id' => '...', 'secret_key' => '...']);
 *   $gateway = PaymentGateway::edfapay(['client_key' => '...', 'password' => '...']);
 */
final class PaymentGateway
{
    /**
     * Available gateway drivers mapped to their class names.
     *
     * @var array<string, class-string<GatewayInterface>>
     */
    private const DRIVERS = [
        'myfatoorah' => MyFatoorahGateway::class,
        'paylink'    => PaylinkGateway::class,
        'edfapay'    => EdfaPayGateway::class,
    ];

    /**
     * Create a gateway instance by driver name.
     *
     * @param string               $driver  One of: myfatoorah, paylink, edfapay
     * @param array<string, mixed> $config  Gateway-specific configuration
     * @return GatewayInterface
     *
     * @throws \InvalidArgumentException
     */
    public static function create(string $driver, array $config = []): GatewayInterface
    {
        $driver = strtolower(trim($driver));

        if (! isset(self::DRIVERS[$driver])) {
            throw new \InvalidArgumentException(
                "Unsupported payment gateway driver: '{$driver}'. " .
                'Supported drivers: ' . implode(', ', array_keys(self::DRIVERS))
            );
        }

        $config['driver'] = $driver;
        $gatewayClass = self::DRIVERS[$driver];

        return new $gatewayClass(new GatewayConfig($config));
    }

    /**
     * Create a MyFatoorah gateway instance.
     *
     * @param array<string, mixed> $config
     */
    public static function myfatoorah(array $config = []): MyFatoorahGateway
    {
        $config['driver'] = 'myfatoorah';
        return new MyFatoorahGateway(new GatewayConfig($config));
    }

    /**
     * Create a Paylink gateway instance.
     *
     * @param array<string, mixed> $config
     */
    public static function paylink(array $config = []): PaylinkGateway
    {
        $config['driver'] = 'paylink';
        return new PaylinkGateway(new GatewayConfig($config));
    }

    /**
     * Create an EdfaPay gateway instance.
     *
     * @param array<string, mixed> $config
     */
    public static function edfapay(array $config = []): EdfaPayGateway
    {
        $config['driver'] = 'edfapay';
        return new EdfaPayGateway(new GatewayConfig($config));
    }

    /**
     * Get all supported driver names.
     *
     * @return array<int, string>
     */
    public static function getAvailableDrivers(): array
    {
        return array_keys(self::DRIVERS);
    }
}
