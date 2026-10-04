<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnApi\Services;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\System\Paths\Paths;

/**
 * IndexNowService - Manages automated search engine indexing notifications 🚀
 * RBN 3.5 Masterpiece Standard (Part of RbnApi package).
 */
class IndexNowService extends BaseService
{
    /**
     * Sends URL indexing notification to search engines using IndexNow protocol.
     *
     * @param string $domain The domain name/host (e.g. site.example)
     * @param string $projectKey The project identifier to generate verification key
     * @param array $urls List of full URLs to submit
     * @return array Status of the ping operations
     */
    public function ping(string $domain, string $projectKey, array $urls): array
    {
        $cronLogger = $this->storage->logs()->channel('cron');

        if (empty($urls)) {
            return ['success' => false, 'message' => 'Bildirilecek URL listesi boş.'];
        }

        try {
            // Clean domain prefix/ports (IndexNow host must be just domain name, e.g. site.example)
            $cleanDomain = $this->helper('dataSweep')->extractDomain($domain);

            // Yerel ortam (local) kontrolü - Local ise pinglemeden true döner 🌐🔌
            if (is_local()) {
                $cronLogger->info("[{$projectKey}] IndexNow yerel sunucu (local) olduğu için pingleme işlemi atlandı.");
                return ['success' => true, 'message' => 'Yerel sunucu (local) olduğu için pingleme atlandı.'];
            }

            // 1. Deterministic Key Generation
            $indexNowKey = $this->helper('crypto')->hashToken($projectKey . '_indexnow');
            
            // 2. Verification File Creation
            $publicRoot = $this->resolvePublicRoot($projectKey);
            if ($publicRoot) {
                $keyFilePath = $publicRoot . DIRECTORY_SEPARATOR . $indexNowKey . '.txt';
                if (!file_exists($keyFilePath)) {
                    $this->storage->driver()->write($keyFilePath, $indexNowKey);
                    $cronLogger->info("[{$projectKey}] IndexNow doğrulama dosyası oluşturuldu: {$keyFilePath}");
                }
            }

            // 3. Send API Notification
            $pingUrl = 'https://api.indexnow.org/indexnow';
            $payload = [
                'host' => $cleanDomain,
                'key' => $indexNowKey,
                'keyLocation' => "https://{$cleanDomain}/{$indexNowKey}.txt",
                'urlList' => $urls
            ];

            $response = $this->remote->post($pingUrl, $payload, [], true);
            
            if (isset($response['status']) && $response['status'] === 'success') {
                $cronLogger->info("[{$projectKey}] IndexNow ping başarılı. URL sayısı: " . count($urls) . " - Örnek URL: " . ($urls[0] ?? ''));
                return [
                    'success' => true,
                    'message' => 'IndexNow bildirimi başarıyla yapıldı.',
                    'response' => $response
                ];
            }

            $errorMsg = $response['message'] ?? 'Bilinmeyen Hata';
            $cronLogger->error("[{$projectKey}] IndexNow ping başarısız (HTTP Code: " . ($response['http_code'] ?? 'N/A') . "): " . $errorMsg);
            return [
                'success' => false,
                'message' => "IndexNow ping başarısız: {$errorMsg}"
            ];

        } catch (\Exception $e) {
            $cronLogger->error("[{$projectKey}] IndexNow işlemi sırasında kritik hata: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "IndexNow kritik hata: " . $e->getMessage()
            ];
        }
    }

    /**
     * Gets stats and status for IndexNow UI page.
     *
     * @param string $projectKey
     * @param int $urlsCount Count of URLs to be submitted
     * @return array
     */
    public function getStats(string $projectKey, int $urlsCount): array
    {
        $projectData = $this->getProjectData($projectKey);
        $domain = $projectData['domain'] ?? '';
        $cleanDomain = $this->helper('dataSweep')->extractDomain($domain);

        $indexNowKey = $this->helper('crypto')->hashToken($projectKey . '_indexnow');
        $keyLocation = "https://{$cleanDomain}/{$indexNowKey}.txt";

        // Auto-create verification file on page load/stats retrieval
        $publicRoot = $this->resolvePublicRoot($projectKey);
        if ($publicRoot) {
            $keyFilePath = $publicRoot . DIRECTORY_SEPARATOR . $indexNowKey . '.txt';
            if (!file_exists($keyFilePath)) {
                $this->storage->driver()->write($keyFilePath, $indexNowKey);
            }
        }

        $cacheKey = 'api_indexnow_lock';
        $cache = $this->storage->cache();
        $cooldownTimeLeft = null;
        if ($cache) {
            $lastRun = $cache->get($cacheKey);
            if ($lastRun) {
                $secondsSinceLastRun = time() - (int)$lastRun;
                $cooldown = 86400;
                if ($secondsSinceLastRun < $cooldown) {
                    $cooldownTimeLeft = (int)ceil(($cooldown - $secondsSinceLastRun) / 3600);
                }
            }
        }

        return [
            'domain' => $cleanDomain,
            'indexNowKey' => $indexNowKey,
            'keyLocation' => $keyLocation,
            'urlsCount' => $urlsCount,
            'cooldownTimeLeft' => $cooldownTimeLeft
        ];
    }

    /**
     * Performs bulk IndexNow submission for all post URLs.
     *
     * @param string $projectKey
     * @param array $urls List of full URLs to submit
     * @return array Status and result message
     */
    public function pingAll(string $projectKey, array $urls): array
    {
        $cacheKey = 'api_indexnow_lock';
        $cache = $this->storage->cache();

        if ($cache) {
            $lastRun = $cache->get($cacheKey);
            if ($lastRun) {
                $secondsSinceLastRun = time() - (int)$lastRun;
                $cooldown = 86400;
                if ($secondsSinceLastRun < $cooldown) {
                    $timeLeft = $cooldown - $secondsSinceLastRun;
                    $hours = ceil($timeLeft / 3600);
                    return [
                        'success' => false,
                        'type' => 'warning',
                        'message' => "Tüm URL'leri zaten yeni bildirdiniz. Tekrar bildirmek için {$hours} saat beklemeniz gerekiyor."
                    ];
                }
            }
        }

        $projectData = $this->getProjectData($projectKey);
        $domain = $projectData['domain'] ?? '';
        if (empty($domain)) {
            return [
                'success' => false,
                'type' => 'error',
                'message' => 'Proje domaini bulunamadı.'
            ];
        }

        if (empty($urls)) {
            return [
                'success' => false,
                'type' => 'warning',
                'message' => 'Duyuru yapılacak aktif yazı bulunamadı.'
            ];
        }

        $result = $this->ping($domain, $projectKey, $urls);

        if ($result['success'] ?? false) {
            if ($cache) {
                $cache->set($cacheKey, time(), ['ttl' => 86400]);
            }
        }

        return [
            'success' => (bool) ($result['success'] ?? false),
            'type' => ($result['success'] ?? false) ? 'success' : 'error',
            'message' => $result['message'] ?? 'Bir hata oluştu.'
        ];
    }

    /**
     * Helper to ping a single project URL by projectKey and relative path (e.g. /haber/slug)
     */
    public function pingSingleUrl(string $projectKey, string $path): array
    {
        $domain = $this->resolveProjectData('domain', $projectKey);
        if (empty($domain)) {
            return ['success' => false, 'message' => 'Project domain not found'];
        }
        
        $cleanDomain = $this->helper('dataSweep')->extractDomain($domain);
        $fullUrl = "https://{$cleanDomain}/" . ltrim($path, '/');
        
        return $this->ping($domain, $projectKey, [$fullUrl]);
    }

    private function resolvePublicRoot(string $projectKey): ?string
    {
        $publicPath = $this->resolveProjectData('public_path', $projectKey);
        if (!empty($publicPath)) {
            $baseRoot = Paths::workspace();
            $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $publicPath);
            
            // Doğrulama dosyası her zaman domains/ altındaki hedef proje klasörüne yazılır.
            $path = $baseRoot . DIRECTORY_SEPARATOR . 'domains' . DIRECTORY_SEPARATOR . ltrim($normalizedPath, '/\\');
            if (file_exists($path)) {
                return $path;
            }
        }

        // Eğer mevcut aktif proje bağlamı aranan proje ile eşleşiyorsa son çare olarak Paths::publicRoot() dönebiliriz.
        if (function_exists('active_project_key') && active_project_key() === $projectKey) {
            return Paths::publicRoot();
        }

        return null;
    }
}
