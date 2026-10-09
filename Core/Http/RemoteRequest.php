<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Http;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * RemoteRequest - The Universal Outbound HTTP Engine 🪐🛰️⚓
 * 
 * RBN Framework: Centralized requester for all external API communications.
 * Handles headers, methods, and cURL orchestration with peak performance.
 */
class RemoteRequest extends BaseComponent
{
    /**
     * Executes a unified HTTP request to a remote server. 🚀
     */
    public function request(string $method, string $url, array $params = [], array $headers = [], bool $isJson = true, array $options = []): array
    {
        // 🎼 RBN Framework: Execution Safety 🛡️
        if (isset($options['php_timeout'])) {
            set_time_limit((int) $options['php_timeout']);
        }

        $ch = curl_init();
        $method = strtoupper($method);

        // 🎻 1. URL & Params Handling
        if ($method === 'GET' && !empty($params)) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($params);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $options['connect_timeout'] ?? 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, $options['timeout'] ?? 30);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        // 🎻 2. Dynamic/Extra cURL Options ⚙️
        if (!empty($options['curl']) && is_array($options['curl'])) {
            foreach ($options['curl'] as $opt => $val) {
                curl_setopt($ch, $opt, $val);
            }
        }

        // 🛡️ 3. Headers Handling
        $defaultHeaders = [
            'User-Agent: RBN-Core-Bot/1.0'
        ];

        $rawHeaders = array_merge($defaultHeaders, $headers);
        $headers = [];
        foreach ($rawHeaders as $key => $value) {
            if (is_int($key)) {
                $headers[] = $value;
            } else {
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }
                $headers[] = "{$key}: {$value}";
            }
        }

        if ($isJson && $method !== 'GET' && !in_array('Content-Type: application/json', $headers)) {
            $headers[] = 'Content-Type: application/json';
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // 📝 3. Body Handling (POST/PUT/DELETE)
        if ($method !== 'GET' && !empty($params)) {
            $payload = $isJson ? json_encode($params) : http_build_query($params);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        // 🚀 4. Execution with Automated Retry & Circuit Breaker 🛡️
        $maxRetries = (int) ($options['retries'] ?? 1);
        $retryDelayMs = (int) ($options['retry_delay_ms'] ?? 200);
        $attempt = 0;
        $response = false;
        $httpCode = 0;
        $error = '';

        while ($attempt < $maxRetries) {
            $attempt++;
            $response = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);

            // Başarılı veya kalıcı 4xx istemci hatası ise döngüyü kır (retry gereksiz)
            if (!$error && ($httpCode >= 200 && $httpCode < 500)) {
                break;
            }

            // Gecikme ile bir sonraki denemeye geç
            if ($attempt < $maxRetries && $retryDelayMs > 0) {
                usleep($retryDelayMs * 1000);
            }
        }

        // 🛡️ PHP 8.0+ ile curl_close artık işlevsizdir (CurlHandle nesnesi otomatik GC ile temizlenir)

        if ($error || ($httpCode >= 500 || $httpCode === 0)) {
            return [
                'status' => 'error',
                'success' => false,
                'message' => $error ? ('Remote Connection Error: ' . $error) : ("Remote Server Error HTTP {$httpCode}"),
                'http_code' => $httpCode,
                'attempts' => $attempt
            ];
        }

        $decoded = json_decode((string) $response, true);
        $isJsonResult = (json_last_error() === JSON_ERROR_NONE);

        return [
            'status' => ($httpCode >= 200 && $httpCode < 300) ? 'success' : 'remote_error',
            'success' => ($httpCode >= 200 && $httpCode < 300),
            'data' => $isJsonResult ? $decoded : $response,
            'http_code' => $httpCode,
            'raw' => $response,
            'attempts' => $attempt
        ];
    }

    /**
     * Standard GET Request 📥
     */
    public function get(string $url, array $params = [], array $headers = [], array $options = []): array
    {
        return $this->request('GET', $url, $params, $headers, true, $options);
    }

    /**
     * Standard POST Request 📤
     */
    public function post(string $url, array $params = [], array $headers = [], bool $isJson = true, array $options = []): array
    {
        return $this->request('POST', $url, $params, $headers, $isJson, $options);
    }
}
