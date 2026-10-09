<?php
/**
 * RBN RBN Framework - Project Discovery Guard 🛰️🏛️⚓
 */
namespace Rbn\Framework\Core\System\Kernel\Stages;

// Autoloader henüz hazır olmadığı için gerekli sınıfları manuel dahil ediyoruz.
require_once __DIR__ . '/../../Storage/Constants/CacheConstants.php';
require_once __DIR__ . '/../../Discovery/Engine/Cache/ProjectDataMapper.php';

use Rbn\Framework\Core\System\Storage\Constants\CacheConstants;
use Rbn\Framework\Core\System\Discovery\Engine\Cache\ProjectDataMapper;

/**
 * ProjectDiscovery - Dinamik Proje Tespit Mekanizması.
 * Bu sınıf, domain veya proje anahtarı üzerinden önbelleği kontrol eder;
 * cache miss durumunda tüm hydration işini ProjectDataMapper'a devreder.
 */
class ProjectDiscovery
{
    /**
     * Geriye dönük uyumluluk için proxy constant.
     * Asıl tanım ProjectDataMapper::EXTRA_CACHE_KEYS içindedir.
     */
    public const EXTRA_CACHE_KEYS = ProjectDataMapper::EXTRA_CACHE_KEYS;

    /**
     * Mevcut HTTP_HOST üzerinden sadece project_key döner.
     */
    public static function discover(): ?string
    {
        $data = self::getProjectData();
        return $data['project_key'] ?? null;
    }

    /**
     * Mevcut domain veya proje anahtarı üzerinden proje verisini döner.
     * Önce BootCacheProvider'dan okur; cache miss olursa ProjectDataMapper::build() çağırır.
     *
     * @return array Proje verileri
     */
    public static function getProjectData(?string $publicPath = null, ?string $projectKey = null): array
    {
        if (empty($publicPath)) {
            $publicPath = class_exists(\Rbn\Framework\Core\System\Paths\Paths::class)
                && \Rbn\Framework\Core\System\Paths\Paths::isInitialized()
                ? \Rbn\Framework\Core\System\Paths\Paths::publicRoot()
                : ($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__, 5));
        }

        $host = $_SERVER['HTTP_HOST'] ?? '';

        if ($projectKey !== null && $projectKey !== '') {
            $column = 'project_key';
            $value  = $projectKey;
        } else {
            if (empty($host)) {
                return [];
            }
            $column = 'domain';

            // [S-13] Onceki kod `str_replace(['.test','.local'], '', host)` idi:
            // `str_replace` YERINDEKI TUM ESLESMELERI siler, yalnizca SON EKI
            // hedefler. Sonuc: `notest.example.com` -> `no`, `webmytest.io` ->
            // `webmy`, `alocal.net` -> `a`. Boylece gercek bir alan adi yanlis
            // projeye baglanir (kiracı karismasi yuzeyi) ve kesfi onbellegi
            // YANLIS anahtarla yazilir.
            //
            // Duzeltme: yalnizca SONDaki `.test` / `.local` eki atilir.
            // Once `PreBoot::normalizeHost()` ile port atilir, kucuk harfe
            // indirilir ve gecersiz host fail-closed olarak bos stringe duser
            // (TEK normalizasyon merkezi; team member'in S-02 yamasıyla aynı kaynak).
            $host = \Rbn\Framework\Core\System\Kernel\Base\PreBoot::normalizeHost((string) $host);
            if ($host === '') {
                return [];
            }

            $value = (string) preg_replace('/\.(test|local)$/', '', $host);
        }

        $prefix = ($column === 'project_key')
            ? CacheConstants::DISCOVERY_PREFIX_PROJECT
            : CacheConstants::DISCOVERY_PREFIX_DOMAIN;

        // ─── Cache hit → erken dön ───────────────────────────────────────────
        $cached = \Rbn\Framework\Core\System\Storage\Providers\BootCacheProvider::get($value, $publicPath, $prefix);
        if ($cached !== null) {
            return $cached;
        }

        // ─── Cache miss → tüm hydration & cache yazma işini Mapper'a devret ──
        return ProjectDataMapper::build($column, $value, $publicPath, $prefix);
    }
}

