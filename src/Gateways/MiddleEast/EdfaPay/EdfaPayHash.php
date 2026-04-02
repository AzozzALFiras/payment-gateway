<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\MiddleEast\EdfaPay;

/**
 * EdfaPay Hash Generation Utility.
 *
 * Generates the MD5 authentication hash required for all EdfaPay API requests.
 *
 * Hash Formula:
 *   HASH = MD5( UPPERCASE( Reverse(payer_email) + password + Reverse(first6PAN + last4PAN) ) )
 *
 * @link https://docs.edfapay.com/docs/authentication
 */
final class EdfaPayHash
{
    /**
     * Generate the authentication hash for a payment request.
     *
     * @param string $email      Customer's email address
     * @param string $cardNumber Full card number (PAN)
     * @param string $password   Secret hash password from EdfaPay
     * @return string            MD5 hash string
     */
    public static function generate(string $email, string $cardNumber, string $password): string
    {
        $reversedEmail = strrev($email);

        // Extract first 6 digits + last 4 digits of the card number
        $first6 = substr($cardNumber, 0, 6);
        $last4  = substr($cardNumber, -4);
        $reversedPan = strrev($first6 . $last4);

        $baseString = $reversedEmail . $password . $reversedPan;

        return md5(strtoupper($baseString));
    }

    /**
     * Generate hash using already-masked PAN (first6 + last4).
     *
     * @param string $email    Customer's email
     * @param string $first6   First 6 digits of card
     * @param string $last4    Last 4 digits of card
     * @param string $password Secret hash password
     * @return string
     */
    public static function generateFromMasked(string $email, string $first6, string $last4, string $password): string
    {
        $reversedEmail = strrev($email);
        $reversedPan = strrev($first6 . $last4);

        $baseString = $reversedEmail . $password . $reversedPan;

        return md5(strtoupper($baseString));
    }

    /**
     * Generate hash for recurring transactions (no card number needed).
     *
     * For recurring/status operations, the hash may use different fields.
     *
     * @param string $email    Customer's email
     * @param string $password Secret hash password
     * @param string $transId  Transaction ID
     * @return string
     */
    public static function generateForRecurring(string $email, string $password, string $transId): string
    {
        $reversedEmail = strrev($email);
        $reversedTransId = strrev($transId);

        $baseString = $reversedEmail . $password . $reversedTransId;

        return md5(strtoupper($baseString));
    }
}
