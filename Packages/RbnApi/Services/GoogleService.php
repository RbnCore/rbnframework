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
    /**
     * Google Maps üzerinden tüm kurumsal verileri (puan, saatler, adres vb.) çeker 🗺️📍🛰️
     * 
     * @param string $placeId
     * @param string $referer
     * @return array|bool
     */
    public function getCompanyGoogleData(string $placeId, string $referer, ?string $projectKey = null): array|bool
    {
        /** @var \Rbn\Framework\Packages\RbnApi\Providers\GoogleMapsProvider $provider */
        $provider = $this->provider('googleMaps');
        
        $result = $provider->getPlaceDetails($placeId, $referer, $projectKey);

        if ($result['status'] === 'success' && isset($result['data']['id'])) {
            $data = $result['data'];
            return [
                'rating' => [
                    'score' => (string) ($data['rating'] ?? '0.0'),
                    'count' => (string) ($data['userRatingCount'] ?? '0')
                ],
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
