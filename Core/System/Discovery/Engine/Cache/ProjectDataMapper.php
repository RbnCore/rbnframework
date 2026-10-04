<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Discovery\Engine\Cache;

use Rbn\Framework\Core\System\Config\Definitions\DbProfiles\MasterDbData;
use Rbn\Framework\Core\System\Config\Definitions\DbProfiles\CommonDbData;
use Rbn\Framework\Core\System\Storage\Constants\CacheConstants;
use Rbn\Framework\Core\System\Storage\Providers\BootCacheProvider;

/**
 * ProjectDataMapper - Proje Verisi Hidrasyon & Önbellek Yazıcısı 🗺️💾🏛️
 *
 * RBN 3.5: [SINGLE RESPONSIBILITY]
 * Yalnızca iki PDO bağlantısı (Master + Proje) açar, tüm tabloları bildirimsel
 * (declarative) sorgu matrisi üzerinden çeker, birleştirir ve BootCacheProvider
 * aracılığıyla diske mühürler. Yeni bir tablo eklemek = 1 satır yazmak.
 */
class ProjectDataMapper
{
    // =========================================================================
    // [ DECLARATIVE QUERY MATRIX ] 🏛️🧬
    // Yeni Master tablosu eklemek → MASTER_QUERIES'e 1 satır
    // Yeni Proje tablosu eklemek → PROJECT_QUERIES'e 1 satır
    // =========================================================================

    /**
     * Master DB'den proje anahtarıyla çekilecek sorgular.
     * Sonuçlar $masterData[<key>] olarak saklanır.
     */
    private const MASTER_QUERIES = [
        'settings' => "SELECT setting_key, setting_value FROM settings WHERE is_active = 1",
        'cron_jobs' => "SELECT * FROM cron_jobs WHERE project_key = ? AND is_active = 1 ORDER BY id ASC",
    ];

    /**
     * Common DB'den proje anahtarıyla çekilecek sorgular.
     * GLOBAL varsayılanları ve projeye özel kuralları çeker.
     */
    private const COMMON_QUERIES = [
        'shield' => "SELECT setting_key, setting_value, project_key FROM cm_sys_settings_shield WHERE project_key IN (?, 'GLOBAL') ORDER BY (project_key = 'GLOBAL') ASC",
    ];

    /**
     * Proje DB'sinden proje anahtarıyla çekilecek sorgular.
     * z_settings_api üzerinden projeye veya gruba (shared) ait ayarları çeker.
     * Tablo yoksa runQueryMatrix try-catch ile sessizce boş döner.
     */
    private const PROJECT_QUERIES = [
        'options' => "SELECT setting_type AS group_key, setting_key AS option_key, setting_value AS option_value FROM z_settings_api WHERE (project_key = ? OR (group_key = ? AND (project_key = 'shared' OR project_key IS NULL OR project_key = ''))) AND is_active = 1 ORDER BY (project_key != 'shared' AND project_key IS NOT NULL AND project_key != '') ASC",
    ];

    /**
     * Bildirimsel ek önbellek anahtarları (settings_options üzerinden çözümlenir).
     * Yeni bir önbellek alanı eklemek = buraya 1 satır.
     */
    public const EXTRA_CACHE_KEYS = [
        'cron_notification_emails' => [
            'option_key'   => 'CRON_NOTIFICATION_EMAILS',
            'type'         => 'email_list',
            'merge_master' => true,
        ],
    ];

    // =========================================================================
    // [ PUBLIC ENTRY POINT ] 🚀
    // =========================================================================

    /**
     * Cache miss sonrasında çağrılır. İki PDO bağlantısıyla tüm proje verisini
     * toplayıp birleştirir, BootCacheProvider'a yazar ve sonucu döner.
     */
    public static function build(
        string $column,
        string $value,
        string $publicPath,
        string $prefix
    ): array {
        try {
            // ─── 1. MASTER PDO ─────────────────────────────────────────────
            $masterPdo = self::connectDb(MasterDbData::class);

            // Temel proje satırını çek
            $stmt = $masterPdo->prepare(
                "SELECT * FROM projects WHERE {$column} = ? AND status IN ('active', 'maintenance', 'suspended') LIMIT 1"
            );
            $stmt->execute([$value]);
            $baseRow = $stmt->fetch();

            if (empty($baseRow)) {
                return [];
            }

            $projectKey = (string) ($baseRow['project_key'] ?? '');

            // MASTER_QUERIES matrisini çalıştır
            $masterData = self::runQueryMatrix($masterPdo, self::MASTER_QUERIES, $projectKey);

            // Grup projelerini de önbelleğe al (bağlantı henüz açık)
            $groupName    = $baseRow['project_group'] ?? $projectKey;
            $groupProjects = [];
            if (!empty($groupName)) {
                $gStmt = $masterPdo->prepare(
                    "SELECT * FROM projects WHERE (project_group = ? OR project_key = ?) AND status IN ('active', 'maintenance', 'suspended')"
                );
                $gStmt->execute([$groupName, $groupName]);
                $groupProjects = $gStmt->fetchAll() ?: [];
            }

            // Master PDO'yu serbest bırak
            $masterPdo = null;

            // ─── 2. WORKSPACE YOLUNU ÇÖZ ───────────────────────────────────
            require_once __DIR__ . '/../../../Paths/Paths.php';
            $workspacePath = \Rbn\Framework\Core\System\Paths\Paths::workspace();
            $customPath    = 'projects' . DIRECTORY_SEPARATOR
                . ltrim($baseRow['custom_path'] ?? $projectKey, './\\');

            // ─── 3. COMMON PDO & PROJE PDO ──────────────────────────────────
            $commonPdo  = self::connectDb(CommonDbData::class);
            $commonData = self::runQueryMatrix($commonPdo, self::COMMON_QUERIES, [$projectKey, $groupName]);
            $commonPdo  = null;

            $projectPdo  = self::buildProjectPdo($baseRow, $workspacePath, $customPath);
            $projectData = [];
            if ($projectPdo !== null) {
                $projectData = self::runQueryMatrix($projectPdo, self::PROJECT_QUERIES, [$projectKey, $groupName]);
                $projectPdo  = null; // Proje PDO'yu serbest bırak
            }
            $projectData = array_merge($projectData, $commonData);

            // ─── 4. BİRLEŞTİR & FORMATLA ───────────────────────────────────
            $result = self::assemble($baseRow, $masterData, $projectData, $workspacePath, $customPath, $projectKey);

            // ─── 5. ÖNBELLEĞE YAZ ──────────────────────────────────────────
            BootCacheProvider::set($value, $result, $publicPath, $prefix);

            // Grup önbelleğini yaz
            if (!empty($groupProjects)) {
                BootCacheProvider::set(
                    $groupName,
                    $groupProjects,
                    $publicPath,
                    CacheConstants::DISCOVERY_PREFIX_GROUP
                );
            }

            return $result;

        } catch (\Throwable $e) {
            return [];
        }
    }

    // =========================================================================
    // [ PRIVATE: QUERY MATRIX RUNNER ] 🔄
    // =========================================================================

    /**
     * Verilen sorgu matrisini tek PDO bağlantısı üzerinde çalıştırır.
     * Parametre gerektiren sorgular $params ile beslenir.
     *
     * @param string|array $params Tek parametre veya parametre dizisi
     * @return array<string, array> Sorgu anahtarı → satır dizisi
     */
    private static function runQueryMatrix(\PDO $pdo, array $queries, string|array $params): array
    {
        $paramList = is_array($params) ? array_values($params) : [$params];
        $result = [];
        foreach ($queries as $key => $sql) {
            try {
                $paramCount = substr_count($sql, '?');
                $execParams = array_slice($paramList, 0, $paramCount);
                $stmt       = $pdo->prepare($sql);
                $stmt->execute($execParams);
                $result[$key] = $stmt->fetchAll() ?: [];
            } catch (\Throwable $e) {
                $result[$key] = [];
            }
        }
        return $result;
    }

    // =========================================================================
    // [ PRIVATE: ASSEMBLE ] 🏗️
    // =========================================================================

    /**
     * Ham DB verilerini alıp nihai proje önbellek dizisini üretir.
     */
    private static function assemble(
        array  $baseRow,
        array  $masterData,
        array  $projectData,
        string $workspacePath,
        string $customPath,
        string $projectKey
    ): array {
        $result = $baseRow;

        // ─── Routemap: dashboard_prefix & admin_panel_disabled ──────────────
        $routeMapPath    = $workspacePath . DIRECTORY_SEPARATOR . $customPath
            . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'Config'
            . DIRECTORY_SEPARATOR . 'project-routemap.php';
        $routeMap        = [];
        $dashboardPrefix = null;
        $adminDisabled   = false;

        if (file_exists($routeMapPath)) {
            try {
                $routeMap        = (array) (include $routeMapPath);
                $viewMapping     = $routeMap['view_mapping'][$projectKey] ?? [];
                $dashboardPrefix = !empty($viewMapping['dashboard_prefix']) ? (string) $viewMapping['dashboard_prefix'] : null;
                $adminDisabled   = (bool) ($viewMapping['admin_panel_disabled'] ?? false);
            } catch (\Throwable $e) {
            }
        }
        $result['admin_panel_disabled'] = $adminDisabled;
        if ($dashboardPrefix !== null && !$adminDisabled) {
            $result['dashboard_prefix'] = $dashboardPrefix;
        }

        // ─── Master settings map ─────────────────────────────────────────────
        $masterSettingsMap = [];
        foreach ($masterData['settings'] ?? [] as $row) {
            $masterSettingsMap[$row['setting_key']] = $row['setting_value'];
        }

        // ─── Shield ayarları (bot_activity, use_master_api, required_login_role) ─────────────────
        $botActivity  = false;
        $useMasterApi = false;
        $result['required_login_role'] = null;
        foreach ($projectData['shield'] ?? [] as $row) {
            match ($row['setting_key']) {
                'bot_activity'         => $botActivity  = ((int) $row['setting_value'] === 1),
                'use_master_api'       => $useMasterApi = ((int) $row['setting_value'] === 1),
                'required_login_role'  => $result['required_login_role'] = (string) $row['setting_value'],
                'admin_panel_disabled' => $result['admin_panel_disabled'] = (
                    (int) $row['setting_value'] === 1 || $row['setting_value'] === 'true'
                ),
                default => null,
            };
        }

        // ─── Options map & api_keys ──────────────────────────────────────────
        $optionsMap = [];
        $apiKeys    = [];
        foreach ($projectData['options'] ?? [] as $row) {
            $optionsMap[$row['option_key']] = $row['option_value'];
            if ($botActivity && in_array($row['group_key'], ['api', 'bot'], true)) {
                $apiKeys[$row['option_key']] = $row['option_value'];
            }
        }

        // ─── Master API key fallback ─────────────────────────────────────────
        if ($botActivity && $useMasterApi) {
            require_once dirname(__DIR__, 5) . '/Packages/RbnApi/Models/ApiKeysRegistry.php';
            $mapping = $routeMap['view_mapping'][$projectKey] ?? [];
            foreach (\Rbn\Framework\Packages\RbnApi\Models\ApiKeysRegistry::MAP as $platform => $val) {
                $hasOwn = false;
                if (is_array($val)) {
                    $hasOwn = true;
                    foreach ($val as $envKey) {
                        if (empty($apiKeys[$envKey])) { $hasOwn = false; break; }
                    }
                } else {
                    $hasOwn = !empty($apiKeys[$val]);
                }

                if (!$hasOwn && !empty($mapping['api_' . $platform])) {
                    if (is_array($val)) {
                        foreach ($val as $envKey) {
                            if (empty($apiKeys[$envKey]) && !empty($masterSettingsMap[$envKey])) {
                                $apiKeys[$envKey] = $masterSettingsMap[$envKey];
                            }
                        }
                    } elseif (empty($apiKeys[$val]) && !empty($masterSettingsMap[$val])) {
                        $apiKeys[$val] = $masterSettingsMap[$val];
                    }
                }
            }
        }

        $result['api_keys'] = $apiKeys;

        // ─── EXTRA_CACHE_KEYS bildirimsel döngüsü ────────────────────────────
        foreach (self::EXTRA_CACHE_KEYS as $resKey => $cfg) {
            $optKey      = $cfg['option_key'];
            $type        = $cfg['type'] ?? 'string';
            $mergeMaster = $cfg['merge_master'] ?? false;
            $items       = [];

            // Projeye özel ayar tanımlıysa öncelikli olarak onu kullan; yoksa ve mergeMaster aktifse Master DB'ye fallback yap 🛡️
            if (!empty($optionsMap[$optKey])) {
                $raw   = $optionsMap[$optKey];
                $items = is_array($raw)
                    ? $raw
                    : preg_split('/[\s,;]+/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY);
            } elseif ($mergeMaster && !empty($masterSettingsMap[$optKey])) {
                $raw   = $masterSettingsMap[$optKey];
                $items = is_array($raw)
                    ? $raw
                    : preg_split('/[\s,;]+/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY);
            }

            if ($type === 'email_list') {
                $valid = [];
                foreach ($items as $email) {
                    $email = trim((string) $email);
                    if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $valid[] = $email;
                    }
                }
                $result[$resKey] = array_values(array_unique($valid));
            } else {
                $result[$resKey] = !empty($items)
                    ? (count($items) === 1 ? $items[0] : $items)
                    : null;
            }
        }

        // ─── Cron tasks ──────────────────────────────────────────────────────
        $result['cron_tasks'] = self::normalizeCronTasks($masterData['cron_jobs'] ?? []);

        return self::sanitizeResult($result);
    }

    // =========================================================================
    // [ PRIVATE: HELPERS ] 🔧
    // =========================================================================

    /**
     * Önbelleğe mühürlenmeden önce gereksiz, geçersiz veya koşula bağlı
     * istenmeyen anahtarları merkezi kurallarla temizler 🧹✨
     */
    private static function sanitizeResult(array $result): array
    {
        // 1. Cron Görevi Yoksa: cron bildirim maillerini ve boş cron_tasks dizisini yazma
        if (empty($result['cron_tasks'])) {
            unset($result['cron_notification_emails'], $result['cron_tasks']);
        }

        // 2. API Key Yoksa: boş api_keys dizisini yazma
        if (empty($result['api_keys'])) {
            unset($result['api_keys']);
        }

        // 3. Login Rolü Zorunluluğu Yoksa: null/boş required_login_role alanını yazma
        if (empty($result['required_login_role'])) {
            unset($result['required_login_role']);
        }

        // 4. Admin Paneli Kapalıysa: dashboard_prefix anahtarını kaldır, Açıksa (false): admin_panel_disabled alanını yazma
        $isAdminDisabled = !empty($result['admin_panel_disabled']) 
            && in_array($result['admin_panel_disabled'], [true, 1, '1', 'true'], true);

        if ($isAdminDisabled) {
            unset($result['dashboard_prefix']);
            $result['admin_panel_disabled'] = true;
        } else {
            unset($result['admin_panel_disabled']);
        }

        // 5. Options Yoksa: null/boş options alanını yazma
        if (empty($result['options'])) {
            unset($result['options']);
        }

        return $result;
    }

    /**
     * Ham cron_jobs satırlarını normalize edilmiş görev dizisine çevirir.
     */
    private static function normalizeCronTasks(array $rows): array
    {
        $tasks = [];
        foreach ($rows as $row) {
            $params = [];
            if (!empty($row['params'])) {
                $decoded = json_decode((string) $row['params'], true);
                $params  = is_array($decoded) ? $decoded : [];
            }
            $tasks[] = [
                'id'          => (int)    $row['id'],
                'project_key' => (string) $row['project_key'],
                'name'        => (string) ($row['name'] ?? ''),
                'task_key'    => (string) ($row['task_key'] ?? ''),
                'task_class'  => (string) ($row['task_class'] ?? ''),
                'frequency'   => (int)    ($row['frequency'] ?? 0),
                'last_run_at' => $row['last_run_at'] ?? null,
                'next_run_at' => $row['next_run_at'] ?? null,
                'is_active'   => (int)    ($row['is_active'] ?? 1),
                'params'      => $params,
            ];
        }
        return $tasks;
    }

    /**
     * Proje ayar dosyasını okuyarak proje PDO bağlantısı kurar.
     */
    private static function buildProjectPdo(array $baseRow, string $workspacePath, string $customPath): ?\PDO
    {
        $settingsPath = $workspacePath . DIRECTORY_SEPARATOR . $customPath
            . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'Config'
            . DIRECTORY_SEPARATOR . 'project-settings.php';

        if (!file_exists($settingsPath)) {
            return null;
        }

        try {
            $cfg    = (array) (include $settingsPath);
            $map    = \Rbn\Framework\Core\System\Config\Definitions\DbProfiles\ProjectDbData::KEYS_MAP;
            $dbName = $cfg[$map['database']] ?? '';
            if (empty($dbName)) {
                return null;
            }
            return self::connectDb(
                $cfg[$map['host']]     ?? '127.0.0.1',
                $dbName,
                $cfg[$map['user']]     ?? 'root',
                $cfg[$map['password']] ?? ''
            );
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Düşük gecikmeli PDO bağlantısı üretir. 🔌
     * $dbSource bir DbData sınıfı (örn: MasterDbData::class), credential dizisi veya host string olabilir.
     *
     * @param string|array $dbSource DbData sınıf adı, credentials dizisi veya host IP
     */
    private static function connectDb(
        string|array $dbSource,
        string $dbName = '',
        string $user = 'root',
        string $pass = ''
    ): \PDO {
        if (is_array($dbSource)) {
            $host   = $dbSource['host'] ?? '127.0.0.1';
            $dbName = $dbSource['database'] ?? '';
            $user   = $dbSource['user'] ?? 'root';
            $pass   = $dbSource['password'] ?? '';
        } elseif (in_array($dbSource, [\Rbn\Framework\Core\System\Config\Definitions\DbProfiles\MasterDbData::class, \Rbn\Framework\Core\System\Config\Definitions\DbProfiles\CommonDbData::class, \Rbn\Framework\Core\System\Config\Definitions\DbProfiles\ProjectDbData::class], true)) {
            $creds  = \Rbn\Framework\Core\System\Config\Engine\Database\DbProfileResolver::credentials($dbSource);
            $host   = $creds['host'];
            $dbName = $creds['database'];
            $user   = $creds['user'];
            $pass   = $creds['password'];
        } else {
            $host = (string) $dbSource;
        }

        return new \PDO(
            "mysql:host={$host};dbname={$dbName};charset=utf8mb4",
            $user,
            $pass,
            [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_TIMEOUT            => 2,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]
        );
    }
}
