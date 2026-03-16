<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Support;

/**
 * Array helper utilities.
 */
final class Arr
{
    /**
     * Get a value from a nested array using dot notation.
     *
     * @param array<string, mixed> $array
     */
    public static function get(array $array, string $key, mixed $default = null): mixed
    {
        if (isset($array[$key])) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (! is_array($array) || ! array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * Filter an array removing null and empty string values.
     *
     * @param array<string, mixed> $array
     * @return array<string, mixed>
     */
    public static function clean(array $array): array
    {
        return array_filter($array, fn(mixed $value) => $value !== null && $value !== '');
    }

    /**
     * Pick only the specified keys from an array.
     *
     * @param array<string, mixed> $array
     * @param array<int, string>   $keys
     * @return array<string, mixed>
     */
    public static function only(array $array, array $keys): array
    {
        return array_intersect_key($array, array_flip($keys));
    }
}
