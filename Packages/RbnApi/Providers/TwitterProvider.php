<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnApi\Providers;

use Rbn\Framework\Core\Base\BaseComponent;
class TwitterProvider extends BaseComponent
{
    private string $baseUrl = "https://api.twitter.com/2/";
    private string $uploadUrl = "https://upload.twitter.com/1.1/";

    /**
     * Standard X (Twitter) API Call with OAuth 1.0a Signature 🔑🛡️🤖
     */
    public function call(string $method, array $params = [], string $httpMethod = 'POST', bool $isUpload = false, ?string $projectKey = null): array
    {
        $keys = $this->manager('api')->resolveApiKey('twitter', $projectKey);

        if (!$keys || !is_array($keys) || empty($keys['consumer_key']) || empty($keys['consumer_secret']) || empty($keys['access_token']) || empty($keys['access_token_secret'])) {
            return [
                'status' => 'error',
                'message' => 'Twitter API yetkisi veya anahtarları bulunamadı! 🛡️⚓'
            ];
        }

        $consumerKey = $keys['consumer_key'];
        $consumerSecret = $keys['consumer_secret'];
        $accessToken = $keys['access_token'];
        $accessTokenSecret = $keys['access_token_secret'];

        $url = ($isUpload ? $this->uploadUrl : $this->baseUrl) . ltrim($method, '/');
        if ($isUpload && strpos($url, '.json') === false) {
            $url .= '.json';
        }

        $nonce = bin2hex(random_bytes(16));
        $timestamp = time();

        $oauth = [
            'oauth_consumer_key' => $consumerKey,
            'oauth_nonce' => $nonce,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => $timestamp,
            'oauth_token' => $accessToken,
            'oauth_version' => '1.0'
        ];

        $baseParams = $oauth;
        if (!$isUpload) {
            $baseParams = array_merge($baseParams, $params);
        }

        ksort($baseParams);
        $baseString = $httpMethod . "&" . rawurlencode($url) . "&" . rawurlencode(http_build_query($baseParams, '', '&', PHP_QUERY_RFC3986));
        $signingKey = rawurlencode($consumerSecret) . "&" . rawurlencode($accessTokenSecret);
        $signature = base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));

        $oauth['oauth_signature'] = $signature;
        ksort($oauth);

        $authHeader = 'OAuth ';
        $values = [];
        foreach ($oauth as $key => $value) {
            $values[] = rawurlencode($key) . '="' . rawurlencode((string)$value) . '"';
        }
        $authHeader .= implode(', ', $values);

        $options = [
            'curl' => [
                CURLOPT_HTTPHEADER => ['Authorization: ' . $authHeader],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false
            ]
        ];

        if ($httpMethod === 'POST') {
            $options['curl'][CURLOPT_POST] = true;
            $options['curl'][CURLOPT_POSTFIELDS] = $isUpload ? $params : json_encode($params);
            if (!$isUpload) {
                $options['curl'][CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
            }
        }

        return $this->remote->request($httpMethod, $url, $params, $options);
    }
}
