<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnApi\Services;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\Base\Attributes\Component;

/**
 * GoogleService - Core API Bridge for Google Cloud Ecosystem 🛰️🏛️⚓
 * RBN Framework Framework Standard.
 */
#[Component(alias: 'google', type: 'service')]
class GoogleService extends BaseService
{
    /** Başarılı Places yanıtı önbellek süresi (TEK KAYNAK): 7 gün. */
    public const COMPANY_DATA_TTL = 604800;
    /** Başarısız yanıtta yalnız "tekrar deneme" işareti tutulur (veri DEĞİL): 15 dk. */
    public const COMPANY_DATA_RETRY_TTL = 900;

    /**
     * Google Maps üzerinden kurumsal verileri (puan, saatler, adres vb.) önbellekli çeker 🗺️📍🛰️
     *
     * Tek kaynak davranışı: başarılı yanıt 7 gün (place id + projectKey bazlı) önbellekte; başarısız
     * yanıt ASLA veri olarak önbelleğe yazılmaz, 15 dk retry işareti konur ve `false` döner;
     * gömülü/uydurma yedek değer YOK. Başarıda dönüş `rating_is_live => true` taşır.
     * Aynı projectKey + place id veren siteler aynı kaydı paylaşır.
     *
     * @return array|bool veri ya da false (alınamadı; çağıran puanı GİZLER)
     */
    public function getCompanyGoogleData(string $placeId, string $referer, ?string $projectKey = null): array|bool
    {
        $cache = $this->companyCache()->withProject($projectKey);
        $dataKey = 'api_google_place_' . $placeId;
        $retryKey = $dataKey . '_retry';

        try {
            $cached = $cache->get($dataKey);
            if (is_array($cached)) {
                return $cached;
            }

            if ($cache->get($retryKey)) {
                return false;
            }

            $data = $this->fetchCompanyGoogleData($placeId, $referer, $projectKey);
            if (is_array($data)) {
                $cache->set($dataKey, $data, ['ttl' => self::COMPANY_DATA_TTL]);

                return $data;
            }

            $cache->set($retryKey, true, ['ttl' => self::COMPANY_DATA_RETRY_TTL]);

            return false;
        } finally {
            $cache->withProject(null);
        }
    }

    /** Önbellek erişimi (test dikişi). */
    protected function companyCache(): object
    {
        return $this->storage->cache();
    }

    /** Ham Places çağrısı; başarısızsa false. */
    protected function fetchCompanyGoogleData(string $placeId, string $referer, ?string $projectKey): array|bool
    {
        /** @var \Rbn\Framework\Packages\RbnApi\Providers\GoogleMapsProvider $provider */
        $provider = $this->provider('googleMaps');

        $result = $provider->getPlaceDetails($placeId, $referer, $projectKey);

        if ($result['status'] === 'success' && isset($result['data']['id']) && isset($result['data']['userRatingCount'])) {
            $data = $result['data'];
            return [
                'rating' => [
                    'score' => (string) ($data['rating'] ?? '0.0'),
                    'count' => (string) $data['userRatingCount']
                ],
                'rating_is_live' => true,
                'address' => $data['formattedAddress'] ?? '',
                'phone'   => $data['nationalPhoneNumber'] ?? '',
                'location' => $data['location'] ?? [],
                'hours'    => $data['currentOpeningHours'] ?? []
            ];
        }

        return false;
    }

    /**
     * Checks if Google Analytics is active for a project
     */
    public function isAnalyticsActive(?string $projectKey = null): bool
    {
        $projectKey = $projectKey ?: active_project_key();
        return $this->provider('googleAnalytics')->isActive($projectKey);
    }

    /**
     * Fetch GA4 reports for the given project key (standard or map version) 📊🗺️⚡
     */
    public function getAnalyticsReport(?string $projectKey = null, ?string $startDate = null, ?string $endDate = null, string $type = 'standard'): array
    {
        $projectKey = $projectKey ?: active_project_key();
        
        // Cache management at service level with dynamic date tags ⚡
        $dateTag = ($startDate ? '_' . str_replace('-', '', $startDate) : '') . ($endDate ? '_' . str_replace('-', '', $endDate) : '');
        $cacheKey = 'api_ga4_report_' . $type . $dateTag;
        
        $cacheStorage = $this->storage->cache();
        if ($projectKey) {
            $cacheStorage->projectKey = $projectKey;
        }

        $cached = $cacheStorage->get($cacheKey);
        if ($cached !== null && is_array($cached)) {
            return $cached;
        }

        // Delegate to package provider depending on type
        $provider = $this->provider('googleAnalytics');
        if ($type === 'map') {
            $data = $provider->getTurkeyCityReports($projectKey, $startDate, $endDate);
        } else {
            $data = $provider->getReports($projectKey, $startDate, $endDate);
        }
        
        if (!is_array($data)) {
            $data = ['active' => false];
        }

        $data['cached_at'] = date('Y-m-d H:i:s');
        // Cache in framework cache for 30 minutes (prevents API hanging loop)
        $cacheStorage->set($cacheKey, $data, ['ttl' => 1800]);
        
        return $data;
    }
}
