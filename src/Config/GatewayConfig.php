<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Config;

/**
 * Immutable gateway configuration data object.
 *
 * @property-read string $driver      The gateway driver name
 * @property-read bool   $testMode    Whether sandbox/test mode is active
 * @property-read int    $timeout     HTTP request timeout in seconds
 */
final class GatewayConfig
{
    /** @var array<string, mixed> */
    private array $attributes;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $defaults = [
            'driver'   => '',
            'testMode' => true,
            'timeout'  => 30,
        ];

        $this->attributes = array_merge($defaults, $config);
    }

    /**
     * Get a configuration value by key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Check if a configuration key exists and is not empty.
     */
    public function has(string $key): bool
    {
        return isset($this->attributes[$key]) && $this->attributes[$key] !== '';
    }

    /**
     * Get a required configuration value, throw if missing.
     *
     * @throws \InvalidArgumentException
     */
    public function require(string $key): mixed
    {
        if (! $this->has($key)) {
            throw new \InvalidArgumentException(
                "Missing required configuration key: '{$key}'"
            );
        }

        return $this->attributes[$key];
    }

    /**
     * Check if running in test/sandbox mode.
     */
    public function isTestMode(): bool
    {
        return (bool) ($this->attributes['testMode'] ?? true);
    }

    /**
     * Get the HTTP timeout.
     */
    public function getTimeout(): int
    {
        return (int) ($this->attributes['timeout'] ?? 30);
    }

    /**
     * Return all config as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }
}
