<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnApi\Providers;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * GoogleMapsProvider - Official Google Places API Data Fetcher 🛰️📍⚓
 * RBN 3.5 Masterpiece Standard.
 */
class GoogleMapsProvider extends BaseComponent
{
    private string $baseUrl = "https://places.googleapis.com/v1/places/";

    /**
     * Google Places API üzerinden yer bilgilerini çeker 📡
     * 
     * @param string $placeId Google Place ID
     * @param string $referer Güvenlik kısıtlaması için referer URL
     * @return array
     */
    public function getPlaceDetails(string $placeId, string $referer, ?string $projectKey = null): array
    {
        // 🛡️ EXCLUSIVE IDENTITY LOGIC: Otonom Güvenlik ve Key Çözümleyici 🏛️🛰️⚓
        $resolved = $this->manager('api')->resolveApiKey('google', $projectKey);
        $apiKey = is_array($resolved) ? ($resolved['api_key'] ?? '') : '';

        if (empty($apiKey)) {
            return [
                'status' => 'error',
                'message' => 'RBN 3.5: Google API yetkisi veya anahtarı bulunamadı! 🛡️⚓'
            ];
        }

        $endpoint = $this->baseUrl . $placeId . "?fields=id,rating,userRatingCount,currentOpeningHours,formattedAddress,nationalPhoneNumber,location&languageCode=tr";

        // 🪐 RBN Sovereign Remote Engine!
        $result = $this->remote->get($endpoint, [], [
            'X-Goog-Api-Key' => $apiKey,
            'X-Goog-FieldMask' => 'id,rating,userRatingCount,currentOpeningHours,formattedAddress,nationalPhoneNumber,location',
            'Referer' => $referer
        ]);

        if ($result['status'] !== 'success') {
            return [
                'status' => 'error',
                'message' => 'Google API Hatası: ' . ($result['data']['error']['message'] ?? 'Bilinmeyen Hata')
            ];
        }

        return [
            'status' => 'success',
            'data' => $result['data']
        ];
    }
}
