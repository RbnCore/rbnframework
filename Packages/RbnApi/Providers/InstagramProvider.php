<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnApi\Providers;

use Rbn\Framework\Core\Base\BaseComponent;
class InstagramProvider extends BaseComponent
{
    private string $baseUrl = 'https://graph.facebook.com/v19.0/';

    /**
     * Instagram Graph API'sine çağrı yapar. 🚀
     */
    public function call(string $method, array $params = [], string $httpMethod = 'GET', ?string $projectKey = null): array
    {
        $tokenData = $this->manager('api')->resolveApiKey('instagram', $projectKey);
        $accessToken = is_array($tokenData) ? ($tokenData['access_token'] ?? ($tokenData['api_key'] ?? '')) : (string) $tokenData;

        if (empty($accessToken)) {
            return [
                'status' => 'error',
                'message' => 'RBN 3.5: Instagram Access Token yetkisi veya anahtarı bulunamadı! 🛡️⚓'
            ];
        }

        $params['access_token'] = $accessToken;
        $url = $this->baseUrl . ltrim($method, '/');

        // 🪐 Use the Sovereign Remote Engine! (Meta Graph API requires form-urlencoded for POST)
        $result = $this->remote->request($httpMethod, $url, $params, [], false);

        if ($result['status'] === 'success') {
            return [
                'status' => 'success',
                'data' => $result['data']
            ];
        }

        $metaError = $result['data']['error']['message'] ?? null;
        $errMsg = $metaError ?: ($result['message'] ?? 'Instagram API request failed');

        return [
            'status' => 'error',
            'message' => $errMsg,
            'details' => $result['data'] ?? []
        ];
    }
}
