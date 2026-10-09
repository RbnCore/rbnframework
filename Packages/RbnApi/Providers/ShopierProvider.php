<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnApi\Providers;

use Rbn\Framework\Core\Base\BaseComponent;
class ShopierProvider extends BaseComponent
{
    private string $baseUrl = 'https://api.shopier.com/v1/';

    /**
     * Shopier REST API'sine çağrı yapar. 🚀
     */
    public function call(string $method, array $params = [], string $httpMethod = 'GET', ?string $projectKey = null): array
    {
        $accessToken = $this->manager('api')->resolveApiKey('shopier', $projectKey);

        if (empty($accessToken)) {
            return [
                'status' => 'error',
                'message' => 'Sanal POS aktif değil veya erişim anahtarı (Access Token) tanımlanmamış.'
            ];
        }

        $url = $this->baseUrl . ltrim($method, '/');
        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json'
        ];

        // 🪐 RBN Framework: RemoteRequest Engine (GET sorgu dizesi; POST/PUT/DELETE JSON gövde)
        return $this->remote->request(strtoupper($httpMethod), $url, $params, $headers);
    }
}
