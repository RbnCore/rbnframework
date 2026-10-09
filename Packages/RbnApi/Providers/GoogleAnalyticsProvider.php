<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnApi\Providers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\System\Paths\Paths;

/**
 * GoogleAnalyticsProvider - Google Analytics 4 (GA4) API Data Engine 📊🛰️⚓
 * RBN Framework Standard.
 */
class GoogleAnalyticsProvider extends BaseComponent
{
    private string $tokenUrl = 'https://oauth2.googleapis.com/token';
    private string $scope = 'https://www.googleapis.com/auth/analytics.readonly';

    /**
     * Retrieve Property ID from API manager
     */
    public function getPropertyId(string $projectKey): string
    {
        $resolved = $this->manager('api')->resolveApiKey('google', $projectKey);
        return is_array($resolved) ? (string) ($resolved['analytics_key'] ?? '') : '';
    }

    private function getServiceAccountFilePath(string $projectKey): string
    {
        $customPath = $this->resolveProjectData('custom_path', $projectKey);
        $folderName = $customPath ?: $projectKey;

        $targetPath = Paths::workspace() . "/projects/{$folderName}/Resources/Data/google-{$projectKey}.json";
        if (file_exists($targetPath)) {
            return $targetPath;
        }

        return Paths::project()->resources("Data/google-{$projectKey}.json");
    }

    /**
     * Check if GA4 configuration is active for the project (Database Property ID + File Credentials)
     */
    public function isActive(string $projectKey): bool
    {
        $propertyId = $this->getPropertyId($projectKey);
        if (empty($propertyId)) {
            return false;
        }

        return file_exists($this->getServiceAccountFilePath($projectKey));
    }

    /**
     * Retrieve Google Service Account JSON configuration from storage disk
     */
    public function getServiceAccountConfig(string $projectKey): ?array
    {
        $keyPath = $this->getServiceAccountFilePath($projectKey);
        if (file_exists($keyPath)) {
            $config = json_decode(file_get_contents($keyPath), true);
            if (is_array($config)) {
                return $config;
            }
        }
        return null;
    }

    /**
     * Fetch GA4 Reports
     */
    public function getReports(string $projectKey, ?string $startDate = null, ?string $endDate = null): array
    {
        if (!$this->isActive($projectKey)) {
            return ['active' => false];
        }

        try {
            $accessToken = $this->getAccessToken($projectKey);
            $propertyId = $this->getPropertyId($projectKey);

            $reportStart = $startDate ?: '30daysAgo';
            $reportEnd = $endDate ?: 'today';
            $trendStart = $startDate ?: '7daysAgo';
            $trendEnd = $endDate ?: 'today';

            // 1. Fetch Traffic Trend
            $trendData = $this->runReport($accessToken, $propertyId, [
                'dateRanges' => [['startDate' => $trendStart, 'endDate' => $trendEnd]],
                'dimensions' => [['name' => 'date']],
                'metrics' => [
                    ['name' => 'activeUsers'],
                    ['name' => 'screenPageViews'],
                    ['name' => 'sessions']
                ],
                'orderBys' => [
                    ['dimension' => ['dimensionName' => 'date'], 'desc' => false]
                ]
            ]);

            // 2. Fetch Top Pages
            $topPages = $this->runReport($accessToken, $propertyId, [
                'dateRanges' => [['startDate' => $reportStart, 'endDate' => $reportEnd]],
                'dimensions' => [['name' => 'pagePath'], ['name' => 'pageTitle']],
                'metrics' => [['name' => 'screenPageViews']],
                'limit' => 10,
                'orderBys' => [
                    ['metric' => ['metricName' => 'screenPageViews'], 'desc' => true]
                ]
            ]);

            // 3. Fetch Device Distribution
            $devices = $this->runReport($accessToken, $propertyId, [
                'dateRanges' => [['startDate' => $reportStart, 'endDate' => $reportEnd]],
                'dimensions' => [['name' => 'deviceCategory']],
                'metrics' => [['name' => 'activeUsers']],
                'orderBys' => [
                    ['metric' => ['metricName' => 'activeUsers'], 'desc' => true]
                ]
            ]);

            // 4. Fetch Traffic Channels/Sources
            $sources = $this->runReport($accessToken, $propertyId, [
                'dateRanges' => [['startDate' => $reportStart, 'endDate' => $reportEnd]],
                'dimensions' => [['name' => 'sessionSourceMedium']],
                'metrics' => [['name' => 'sessions']],
                'limit' => 10,
                'orderBys' => [
                    ['metric' => ['metricName' => 'sessions'], 'desc' => true]
                ]
            ]);

            // 5. Fetch Geography (Country / City)
            $geography = $this->runReport($accessToken, $propertyId, [
                'dateRanges' => [['startDate' => $reportStart, 'endDate' => $reportEnd]],
                'dimensions' => [['name' => 'country'], ['name' => 'city']],
                'metrics' => [['name' => 'activeUsers']],
                'limit' => 10,
                'orderBys' => [
                    ['metric' => ['metricName' => 'activeUsers'], 'desc' => true]
                ]
            ]);

            // 6. Fetch Technology (Browser / OS)
            $technology = $this->runReport($accessToken, $propertyId, [
                'dateRanges' => [['startDate' => $reportStart, 'endDate' => $reportEnd]],
                'dimensions' => [['name' => 'browser'], ['name' => 'operatingSystem']],
                'metrics' => [['name' => 'activeUsers']],
                'limit' => 10,
                'orderBys' => [
                    ['metric' => ['metricName' => 'activeUsers'], 'desc' => true]
                ]
            ]);

            // Calculate Totals for Summary
            $summary = [
                'activeUsers' => 0,
                'screenPageViews' => 0,
                'sessions' => 0,
                'bounceRate' => 0.0
            ];

            $totalsReport = $this->runReport($accessToken, $propertyId, [
                'dateRanges' => [['startDate' => $reportStart, 'endDate' => $reportEnd]],
                'metrics' => [
                    ['name' => 'activeUsers'],
                    ['name' => 'screenPageViews'],
                    ['name' => 'sessions'],
                    ['name' => 'bounceRate']
                ]
            ]);

            if (!empty($totalsReport['rows'][0]['metricValues'])) {
                $summary['activeUsers'] = (int) ($totalsReport['rows'][0]['metricValues'][0]['value'] ?? 0);
                $summary['screenPageViews'] = (int) ($totalsReport['rows'][0]['metricValues'][1]['value'] ?? 0);
                $summary['sessions'] = (int) ($totalsReport['rows'][0]['metricValues'][2]['value'] ?? 0);
                $summary['bounceRate'] = round((float) ($totalsReport['rows'][0]['metricValues'][3]['value'] ?? 0.0) * 100, 2);
            }

            return [
                'active' => true,
                'property_id' => $propertyId,
                'summary' => $summary,
                'trend' => $this->parseReportRows($trendData),
                'top_pages' => $this->parseReportRows($topPages),
                'devices' => $this->parseReportRows($devices),
                'sources' => $this->parseReportRows($sources),
                'geography' => $this->parseReportRows($geography),
                'technology' => $this->parseReportRows($technology)
            ];

        } catch (\Throwable $e) {
            return [
                'active' => true,
                'error' => true,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Run a report request on the GA4 Data API v1beta
     */
    private function runReport(string $accessToken, string $propertyId, array $requestBody): array
    {
        $url = 'https://analyticsdata.googleapis.com/v1beta/properties/' . $propertyId . ':runReport';

        $result = $this->remote->post($url, $requestBody, [
            'Authorization' => 'Bearer ' . $accessToken
        ], true);

        if ($result['status'] !== 'success') {
            $errMsg = $result['message'] ?? $result['data']['error']['message'] ?? 'API Request Failed';
            throw new \Exception('GA4 API Error: ' . $errMsg);
        }

        return $result['data'];
    }

    /**
     * Generate OAuth2 Access Token using JWT authentication
     */
    private function getAccessToken(string $projectKey): string
    {
        $config = $this->getServiceAccountConfig($projectKey);
        if (!$config || empty($config['private_key']) || empty($config['client_email'])) {
            throw new \Exception('Google Service Account JSON key not configured in settings options.');
        }

        $privateKey = $config['private_key'];
        $clientEmail = $config['client_email'];

        $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
        $now = time();
        $claimSet = json_encode([
            'iss' => $clientEmail,
            'scope' => $this->scope,
            'aud' => $this->tokenUrl,
            'exp' => $now + 3600,
            'iat' => $now
        ]);

        $sweepHelper = $this->helper('DataSweep');
        $jwtInput = $sweepHelper->base64UrlEncode($header) . '.' . $sweepHelper->base64UrlEncode($claimSet);

        $signature = '';
        if (!openssl_sign($jwtInput, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new \Exception('Failed to sign JWT using openssl.');
        }

        $jwt = $jwtInput . '.' . $sweepHelper->base64UrlEncode($signature);

        $result = $this->remote->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ], [], false);

        if ($result['status'] !== 'success' || empty($result['data']['access_token'])) {
            $err = $result['message'] ?? $result['data']['error_description'] ?? 'Auth Request Failed';
            throw new \Exception('Google OAuth Auth Error: ' . $err);
        }

        return $result['data']['access_token'];
    }

    /**
     * Fetch City Reports filtered by Country=Turkey from Google Analytics 4 🗺️
     */
    public function getTurkeyCityReports(string $projectKey, ?string $startDate = null, ?string $endDate = null): array
    {
        $propertyId = $this->getPropertyId($projectKey);
        if (!$propertyId) {
            return ['active' => false, 'message' => 'Property ID is missing.'];
        }

        try {
            $accessToken = $this->getAccessToken($projectKey);
            $reportStart = $startDate ?: date('Y-m-d', strtotime('-29 days'));
            $reportEnd = $endDate ?: date('Y-m-d');

            $geoReport = $this->runReport($accessToken, $propertyId, [
                'dateRanges' => [['startDate' => $reportStart, 'endDate' => $reportEnd]],
                'dimensions' => [['name' => 'region']],
                'metrics' => [['name' => 'activeUsers']],
                'dimensionFilter' => [
                    'filter' => [
                        'fieldName' => 'country',
                        'stringFilter' => [
                            'value' => 'Türki',
                            'matchType' => 'CONTAINS'
                        ]
                    ]
                ],
                'limit' => 250,
                'orderBys' => [
                    ['metric' => ['metricName' => 'activeUsers'], 'desc' => true]
                ]
            ]);

            $parsed = $this->parseReportRows($geoReport);
            $cities = [];
            foreach ($parsed as $item) {
                $cities[] = [
                    'city' => $item['region'] ?? '',
                    'activeUsers' => $item['activeUsers'] ?? 0
                ];
            }

            return [
                'active' => true,
                'property_id' => $propertyId,
                'cities' => $cities
            ];
        } catch (\Throwable $e) {
            return [
                'active' => true,
                'error' => true,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Parse raw API rows
     */
    private function parseReportRows(array $report): array
    {
        $headers = [];
        foreach ($report['dimensionHeaders'] ?? [] as $dh) {
            $headers['dimensions'][] = $dh['name'];
        }
        foreach ($report['metricHeaders'] ?? [] as $mh) {
            $headers['metrics'][] = $mh['name'];
        }

        $results = [];
        foreach ($report['rows'] ?? [] as $row) {
            $item = [];
            foreach ($row['dimensionValues'] ?? [] as $idx => $dv) {
                $name = $headers['dimensions'][$idx] ?? 'dim_' . $idx;
                $item[$name] = $dv['value'];
            }
            foreach ($row['metricValues'] ?? [] as $idx => $mv) {
                $name = $headers['metrics'][$idx] ?? 'met_' . $idx;
                $item[$name] = $mv['value'];
            }
            $results[] = $item;
        }

        return $results;
    }
}
