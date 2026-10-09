<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnApi\Services;

use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * ShopierService - RBN Framework Shopier REST API Connector 💳🛰️⚓
 *
 * Shopier'in ödeme linki yoktur: satış, mağazadaki ürün İLANI üzerinden olur (REST v1, tek PAT jetonu).
 * Bu servis ilanı açar ve fiyatını günceller; ilanı ürünle kalıcı eşlemek projenin işidir
 * (her istekte ilan AÇILMAZ, kimlik projede saklanır).
 * @property \Rbn\Framework\Packages\RbnApi\Providers\ShopierProvider $shopier
 */
class ShopierService extends BaseService
{
    /**
     * Mağazada ürün ilanı açar (POST /products) 🏹
     *
     * $listing: title, description, price (float), stock (int), image (url|yol).
     * Dönüş: success + id + url, ya da success=false + message + http_code.
     */
    public function createListing(array $listing, ?string $projectKey = null): array
    {
        $postData = [
            'title' => (string) $listing['title'],
            'description' => (string) ($listing['description'] ?? $listing['title']),
            'type' => 'physical',
            'priceData' => [
                'currency' => 'TRY',
                'price' => number_format((float) $listing['price'], 2, '.', '')
            ],
            'stockQuantity' => (int) $listing['stock'],
            'customListing' => false, // 🖼️ true ile Shopier resim thumbnail'lerini oluşturmuyor.
            'shippingPayer' => 'sellerPays',
            'media' => [
                [
                    'type' => 'image',
                    'url' => self::publicMediaUrl((string) ($listing['image'] ?? '')),
                    'placement' => 1
                ]
            ]
        ];

        $response = $this->provider('apiShopier')->call('products', $postData, 'POST', $projectKey);

        if (($response['status'] ?? '') === 'success' && !empty($response['data']['id']) && !empty($response['data']['url'])) {
            return [
                'success' => true,
                'id' => (string) $response['data']['id'],
                'url' => (string) $response['data']['url'],
            ];
        }

        return self::failure($response);
    }

    /**
     * İlanın fiyatını günceller (PUT /products/{id}); ilan kimliği ve linki değişmez 🔁
     */
    public function updateListingPrice(string $listingId, float $price, ?string $projectKey = null): array
    {
        $response = $this->provider('apiShopier')->call('products/' . rawurlencode($listingId), [
            'priceData' => [
                'currency' => 'TRY',
                'price' => number_format($price, 2, '.', '')
            ]
        ], 'PUT', $projectKey);

        if (($response['status'] ?? '') === 'success') {
            return ['success' => true, 'id' => $listingId];
        }

        return self::failure($response);
    }

    /**
     * Shopier görseli kendi sunucusundan indirir: yol mutlak HTTPS URL olmalı; yerel ortamda (.test,
     * localhost) erişilemeyen görsel yerine herkese açık yer tutucu kullanılır (aksi halde HTTP 400).
     */
    public static function publicMediaUrl(string $image): string
    {
        $mediaUrl = $image !== '' ? $image : 'images/logo.png';

        if (!str_starts_with($mediaUrl, 'http://') && !str_starts_with($mediaUrl, 'https://')) {
            $mediaUrl = url(ltrim($mediaUrl, '/'));
        }
        if (str_starts_with($mediaUrl, 'http://')) {
            $mediaUrl = 'https://' . substr($mediaUrl, 7);
        }

        $host = (string) parse_url($mediaUrl, PHP_URL_HOST);
        if ($host === '' || $host === 'localhost' || $host === '127.0.0.1' || str_contains($host, '.test') || !str_contains($host, '.')) {
            $mediaUrl = 'https://placehold.co/600x600/0ea5e9/ffffff.png';
        }

        return $mediaUrl;
    }

    private static function failure(array $response): array
    {
        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $message = $data['message'] ?? ($data['errors'][0]['message'] ?? ($response['message'] ?? 'Bilinmeyen API hatası.'));

        return [
            'success' => false,
            'http_code' => (int) ($response['http_code'] ?? 0),
            'message' => 'Shopier API Hatası: ' . $message . ' (HTTP ' . (int) ($response['http_code'] ?? 0) . ')'
        ];
    }
}
