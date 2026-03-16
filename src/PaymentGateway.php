<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway;

use AzozzALFiras\PaymentGateway\Config\GatewayConfig;
use AzozzALFiras\PaymentGateway\Contracts\GatewayInterface;
use AzozzALFiras\PaymentGateway\Gateways\MyFatoorah\MyFatoorahGateway;
use AzozzALFiras\PaymentGateway\Gateways\Paylink\PaylinkGateway;
use AzozzALFiras\PaymentGateway\Gateways\EdfaPay\EdfaPayGateway;
use AzozzALFiras\PaymentGateway\Gateways\Tap\TapGateway;
use AzozzALFiras\PaymentGateway\Gateways\ClickPay\ClickPayGateway;
use AzozzALFiras\PaymentGateway\Gateways\Tamara\TamaraGateway;
use AzozzALFiras\PaymentGateway\Gateways\Thawani\ThawaniGateway;
use AzozzALFiras\PaymentGateway\Gateways\Fatora\FatoraGateway;
use AzozzALFiras\PaymentGateway\Gateways\Payzaty\PayzatyGateway;
use AzozzALFiras\PaymentGateway\Gateways\Payzah\PayzahGateway;
use AzozzALFiras\PaymentGateway\Gateways\Stripe\StripeGateway;

/**
 * Payment Gateway Factory — the single entry point for creating gateway instances.
 *
 * Usage:
 *   $gateway = PaymentGateway::create('myfatoorah', ['api_key' => '...']);
 *   $gateway = PaymentGateway::tap(['secret_key' => '...']);
 *   $gateway = PaymentGateway::clickpay(['server_key' => '...', 'profile_id' => '...']);
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
        'tap'        => TapGateway::class,
        'clickpay'   => ClickPayGateway::class,
        'tamara'     => TamaraGateway::class,
        'thawani'    => ThawaniGateway::class,
        'fatora'     => FatoraGateway::class,
        'payzaty'    => PayzatyGateway::class,
        'payzah'     => PayzahGateway::class,
        'stripe'     => StripeGateway::class,
    ];

    /**
     * Create a gateway instance by driver name.
     *
     * @param string               $driver  e.g. myfatoorah, paylink, edfapay, tap, clickpay, tamara, etc.
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

    // ──────────────────────────────────────────
    //  Typed Factory Methods
    // ──────────────────────────────────────────

    /**
     * @param array<string, mixed> $config
     */
    public static function myfatoorah(array $config = []): MyFatoorahGateway
    {
        $config['driver'] = 'myfatoorah';
        return new MyFatoorahGateway(new GatewayConfig($config));
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function paylink(array $config = []): PaylinkGateway
    {
        $config['driver'] = 'paylink';
        return new PaylinkGateway(new GatewayConfig($config));
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function edfapay(array $config = []): EdfaPayGateway
    {
        $config['driver'] = 'edfapay';
        return new EdfaPayGateway(new GatewayConfig($config));
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function tap(array $config = []): TapGateway
    {
        $config['driver'] = 'tap';
        return new TapGateway(new GatewayConfig($config));
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function clickpay(array $config = []): ClickPayGateway
    {
        $config['driver'] = 'clickpay';
        return new ClickPayGateway(new GatewayConfig($config));
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function tamara(array $config = []): TamaraGateway
    {
        $config['driver'] = 'tamara';
        return new TamaraGateway(new GatewayConfig($config));
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function thawani(array $config = []): ThawaniGateway
    {
        $config['driver'] = 'thawani';
        return new ThawaniGateway(new GatewayConfig($config));
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fatora(array $config = []): FatoraGateway
    {
        $config['driver'] = 'fatora';
        return new FatoraGateway(new GatewayConfig($config));
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function payzaty(array $config = []): PayzatyGateway
    {
        $config['driver'] = 'payzaty';
        return new PayzatyGateway(new GatewayConfig($config));
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function payzah(array $config = []): PayzahGateway
    {
        $config['driver'] = 'payzah';
        return new PayzahGateway(new GatewayConfig($config));
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function stripe(array $config = []): StripeGateway
    {
        $config['driver'] = 'stripe';
        return new StripeGateway(new GatewayConfig($config));
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
