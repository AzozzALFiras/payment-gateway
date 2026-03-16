<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Http;

use AzozzALFiras\PaymentGateway\Exceptions\ApiException;
use AzozzALFiras\PaymentGateway\Exceptions\AuthenticationException;

/**
 * Zero-dependency HTTP client using cURL.
 *
 * Supports JSON and form-data requests with configurable timeouts and headers.
 */
final class HttpClient
{
    private int $timeout;

    /** @var array<string, string> */
    private array $defaultHeaders;

    /**
     * @param array<string, string> $defaultHeaders
     */
    public function __construct(int $timeout = 30, array $defaultHeaders = [])
    {
        $this->timeout = $timeout;
        $this->defaultHeaders = $defaultHeaders;
    }

    /**
     * Send a GET request.
     *
     * @param array<string, string> $headers
     * @return array<string, mixed>
     */
    public function get(string $url, array $headers = []): array
    {
        return $this->request('GET', $url, null, $headers);
    }

    /**
     * Send a POST request with JSON body.
     *
     * @param array<string, mixed>  $data
     * @param array<string, string> $headers
     * @return array<string, mixed>
     */
    public function post(string $url, array $data = [], array $headers = []): array
    {
        return $this->request('POST', $url, $data, $headers, 'json');
    }

    /**
     * Send a POST request with form-data body.
     *
     * @param array<string, mixed>  $data
     * @param array<string, string> $headers
     * @return array<string, mixed>
     */
    public function postForm(string $url, array $data = [], array $headers = []): array
    {
        return $this->request('POST', $url, $data, $headers, 'form');
    }

    /**
     * Send a PUT request with JSON body.
     *
     * @param array<string, mixed>  $data
     * @param array<string, string> $headers
     * @return array<string, mixed>
     */
    public function put(string $url, array $data = [], array $headers = []): array
    {
        return $this->request('PUT', $url, $data, $headers, 'json');
    }

    /**
     * Send a DELETE request.
     *
     * @param array<string, string> $headers
     * @return array<string, mixed>
     */
    public function delete(string $url, array $headers = []): array
    {
        return $this->request('DELETE', $url, null, $headers);
    }

    /**
     * Execute an HTTP request using cURL.
     *
     * @param array<string, mixed>|null $data
     * @param array<string, string>     $headers
     * @return array<string, mixed>
     *
     * @throws ApiException
     * @throws AuthenticationException
     */
    private function request(
        string $method,
        string $url,
        ?array $data = null,
        array $headers = [],
        string $contentType = 'json'
    ): array {
        $ch = curl_init();

        $mergedHeaders = array_merge($this->defaultHeaders, $headers);
        $curlHeaders = [];

        foreach ($mergedHeaders as $key => $value) {
            $curlHeaders[] = "{$key}: {$value}";
        }

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_USERAGENT      => 'AzozzALFiras-PaymentGateway-PHP/1.0',
        ]);

        if ($data !== null && in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            if ($contentType === 'form') {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                $curlHeaders[] = 'Content-Type: application/x-www-form-urlencoded';
            } else {
                $jsonBody = json_encode($data, JSON_THROW_ON_ERROR);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
                $curlHeaders[] = 'Content-Type: application/json';
            }
        }

        if (! empty($curlHeaders)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
        }

        $responseBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($responseBody === false) {
            throw new ApiException(
                "HTTP request failed: {$curlError}",
                (int) $httpCode
            );
        }

        /** @var array<string, mixed>|null $decoded */
        $decoded = json_decode((string) $responseBody, true);

        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            // Return raw response for non-JSON responses
            $decoded = [
                '_raw'       => (string) $responseBody,
                '_http_code' => $httpCode,
            ];
        }

        // Handle HTTP error codes
        if ($httpCode >= 400) {
            if ($httpCode === 401 || $httpCode === 403) {
                throw new AuthenticationException(
                    $decoded['message'] ?? $decoded['Message'] ?? 'Authentication failed',
                    $httpCode,
                    null,
                    $decoded
                );
            }

            throw new ApiException(
                $decoded['message'] ?? $decoded['Message'] ?? "API request failed with HTTP {$httpCode}",
                $httpCode,
                0,
                null,
                $decoded
            );
        }

        return $decoded;
    }

    /**
     * Set a default header on all future requests.
     */
    public function setHeader(string $key, string $value): self
    {
        $this->defaultHeaders[$key] = $value;
        return $this;
    }

    /**
     * Set the Bearer authorization token.
     */
    public function setBearerToken(string $token): self
    {
        return $this->setHeader('Authorization', "Bearer {$token}");
    }
}
