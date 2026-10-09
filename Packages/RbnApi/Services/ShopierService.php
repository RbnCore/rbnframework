<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnApi\Services;

use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * ShopierService - RBN Framework Shopier REST API Connector 💳🛰️⚓
 * 
 * RBN Framework: High-level shared package for Shopier integrations.
 * @property \Rbn\Framework\Packages\RbnApi\Providers\ShopierProvider $shopier
 */
class ShopierService extends BaseService
{
    /**
     * Güvenli Ödeme Linki Oluşturur (Modern REST API) 🏹
     */
    public function generatePaymentUrl(string $orderNo, string $productName, float $totalPrice, ?string $imageUrl = null, ?string $projectKey = null): array
    {
        // 🎼 Step 1: Resim URL'ini belirle 🖼️
        $mediaUrl = $imageUrl;

        // Eğer resim veritabanında boşsa, varsayılan bir site logosunu kullan:
        if (empty($mediaUrl)) {
            $mediaUrl = 'images/logo.png';
        }

        // Eğer resim yerel bir yolsa (/images/products/...), tam (absolute) URL'e çevir 🌐
        if (strpos($mediaUrl, 'http://') !== 0 && strpos($mediaUrl, 'https://') !== 0) {
            $mediaUrl = url(ltrim($mediaUrl, '/'));
        }

        // 🛡️ Shopier güvenli checkout modalı için HTTPS zorunludur. HTTP ise HTTPS'e zorla.
        // Bu dönüşümü burada yaparak paylaşılan framework çekirdeğini (baseUrl) riske atmadan izole şekilde çözeriz!
        if (strpos($mediaUrl, 'http://') === 0) {
            $mediaUrl = 'https://' . substr($mediaUrl, 7);
        }

        // 🛡️ Yerel test ortamlarında (localhost, .test vb.) Shopier API'nin resmi indirebilmesi için public CDN placeholder'ı kullan.
        // Aksi takdirde Shopier API "invalid media url" hatasıyla (HTTP 400) ödemeyi keser.
        $host = parse_url($mediaUrl, PHP_URL_HOST);
        if (empty($host) || $host === 'localhost' || $host === '127.0.0.1' || str_contains($host, '.test') || !str_contains($host, '.')) {
            $mediaUrl = 'https://placehold.co/600x600/0ea5e9/ffffff/png';
        }

        // Shopier API standartlarına göre veri paketi hazırlanır
        $postData = [
            'title' => 'Sipariş #' . $orderNo,
            'description' => $productName . ' - Güvenli Sipariş Ödemesi',
            'type' => 'physical',
            'priceData' => [
                'currency' => 'TRY',
                'price' => number_format($totalPrice, 2, '.', '')
            ],
            'stockQuantity' => 1,
            'customListing' => false, // 🖼️ false olması zorunlu! true ile Shopier resim thumbnail'lerini oluşturmuyor.
            'shippingPayer' => 'sellerPays',
            'media' => [
                [
                    'type' => 'image',
                    'url' => $mediaUrl,
                    'placement' => 1
                ]
            ]
        ];

        // 🪐 RBN Framework: Provider Delegation 🛰️
        $response = $this->provider('apiShopier')->call('products', $postData, 'POST', $projectKey);
        
        if ($response['status'] === 'success' && isset($response['data']['url'])) {
            // 🖼️ CDN Görsel Isıtma Döngüsü (Image Warmup) 🚀
            if (!empty($response['data']['media'][0]['url'])) {
                $cdnUrl = $response['data']['media'][0]['url'];
                
                // HEAD istekleri için hafif bir bağlam (context) oluşturalım
                $context = stream_context_create([
                    'http' => [
                        'method'  => 'HEAD',
                        'timeout' => 2
                    ]
                ]);

                $attempts = 0;
                $maxAttempts = 20; // 20 deneme * 150ms = En fazla 3 saniye bekleme süresi

                while ($attempts < $maxAttempts) {
                    $attempts++;
                    
                    // CDN URL'inin HTTP durum kodunu sorgula
                    $headers = @get_headers($cdnUrl, true, $context);
                    
                    if ($headers && isset($headers[0]) && strpos($headers[0], '200') !== false) {
                        break; // Görsel başarıyla indirildi ve CDN'e işlendi!
                    }

                    usleep(150000); // 150 milisaniye bekle
                }
            }

            return [
                'success' => true,
                'url' => $response['data']['url'],
                'product_id' => $response['data']['id'] ?? ''
            ];
        }

        // Hata durumunda hata mesajını ayıkla
        $result = $response['data'] ?? [];
        $errorMessage = $result['message'] ?? ($result['errors'][0]['message'] ?? ($response['message'] ?? 'Bilinmeyen API hatası.'));
        return [
            'success' => false,
            'message' => 'Shopier API Hatası: ' . $errorMessage . ' (HTTP ' . ($response['http_code'] ?? '0') . ')'
        ];
    }

    /**
     * Shopier API Üzerinden Ürüne Ait Ödemenin Tamamlanıp Tamamlanmadığını Sorgular 🔍
     * Ödeme onaylandıysa Shopier'den alıcı bilgilerini de döndürür.
     */
    public function verifyOrderOnline(string $productId, ?string $projectKey = null): array
    {
        if (empty($productId)) {
            return [
                'success' => false,
                'message' => 'Ürün ID bulunamadı.'
            ];
        }

        // 🪐 RBN Framework: Provider Delegation 🛰️
        $response = $this->provider('apiShopier')->call('orders', ['productId' => $productId], 'GET', $projectKey);

        if ($response['status'] === 'success' && is_array($response['data']) && !empty($response['data'])) {
            $latestOrder = $response['data'][0];
            if (($latestOrder['paymentStatus'] ?? '') === 'paid') {
                // 👤 Shopier'den gelen alıcı bilgilerini çek (shippingAddress)
                $shipping = $latestOrder['shippingAddress'] ?? [];
                $buyerName = trim(($shipping['firstName'] ?? '') . ' ' . ($shipping['lastName'] ?? ''));

                return [
                    'success'          => true,
                    'shopier_order_id' => $latestOrder['id'] ?? '',
                    'buyer'            => [
                        'name'     => $buyerName ?: null,
                        'email'    => $shipping['email']   ?? null,
                        'phone'    => $shipping['phone']   ?? null,
                        'identity' => $shipping['nationalId'] ?? null,
                        'address'  => trim(
                            ($shipping['address']  ?? '') . ' ' .
                            ($shipping['district'] ?? '') . ' / ' .
                            ($shipping['city']     ?? '')
                        ) ?: null,
                    ]
                ];
            }
        }

        $errorMessage = $response['message'] ?? 'Ödeme kaydı henüz tamamlanmamış veya bulunamadı.';
        return [
            'success' => false,
            'message' => $errorMessage
        ];
    }
}
