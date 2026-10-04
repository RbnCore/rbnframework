<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Gatekeepers\Providers;

use Rbn\Framework\Core\Base\Services\BaseProvider;
use Rbn\Framework\Core\Support\Contracts\Base\BaseProviderInterface;
use Rbn\Framework\Core\Support\Exceptions\PreflightException;
use Rbn\Framework\Core\System\Discovery\Clusters\Logic\Definition\Definition;
use PDO;
use PDOException;

/**
 * DatabaseGuardProvider - The Physical Bridge for DB Integrity 🏗️🛰️🔗
 * 
 * RBN 3.5: Masterpiece Standard.
 * Handles low-level IO, parsing, and physical DB probing.
 */
class DatabaseGuardProvider extends BaseProvider implements BaseProviderInterface
{
    /**
     * Load credentials from source (.env or PHP) 📂🧬
     */
    public function loadCredentials(string $category, string $path): array
    {
        // 🛡️ RBN 3.5: [MASTER & COMMON IDENTITY BYPASS] 🏛️⚓
        if ($category === 'database_master') {
            return \Rbn\Framework\Core\System\Config\Engine\Database\DbProfileResolver::credentials(
                \Rbn\Framework\Core\System\Config\Definitions\DbProfiles\MasterDbData::class
            );
        }

        if ($category === 'database_common') {
            return \Rbn\Framework\Core\System\Config\Engine\Database\DbProfileResolver::credentials(
                \Rbn\Framework\Core\System\Config\Definitions\DbProfiles\CommonDbData::class
            );
        }

        $map = Definition::get($category, 'KEYS_MAP') ?? [];
        $requiredKeys = array_values($map);

        // 🎼 RBN 3.5: [PURE PHP ONLY] - Project database must use .php config (Env parsing removed)
        if (!file_exists($path)) {
            throw new PreflightException("Proje yapılandırma dosyası eksik!", "Yol: [{$path}]");
        }

        $source = include $path;
        $creds = [];

        foreach ($requiredKeys as $key) {
            $val = $source[$key] ?? $source[strtolower($key)] ?? null;
            if ($val === null) {
                throw new PreflightException(
                    "Yapılandırma Hatası! [{$category}]",
                    "Zorunlu ayar anahtarı eksik: [{$key}]",
                    "Lütfen {$path} dosyasını kontrol edin."
                );
            }
            $creds[$key] = $val;
        }

        return $creds;
    }

    /**
     * Test physical connection and return PDO 🔗
     */
    public function testConnection(string $category): PDO
    {
        $connectionName = match($category) {
            'database_master' => 'database_master',
            'database_common' => 'database_common',
            default => 'database_project'
        };
        $db = \Rbn\Framework\Core\Database\Database::getInstance();
        // [B-24] Dogrudan saglayici cozumleme: yalniz OKUMA yapiyor, global
        // aktif baglantiyi DEGISTIRMIYOR. `connection($ad)->getPdo()` ayni PDO'yu
        // donduruyordu ama bir yandan da aktif baglantiyi kalici olarak
        // sabitliyordu.
        return $db->getProvider($connectionName)->getPdo();
    }

    /**
     * Fetch existing table names from the database 🧱
     */
    public function fetchTableList(PDO $pdo): array
    {
        $stmt = $pdo->query("SHOW TABLES");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * `fetchProjectData()`nın okuduğu GERÇEK kolon listesi. 🎯
     *
     * [G-17 · 2026-10-03 · zeki-6eb7f5] `SELECT *` yerine AÇIK liste.
     * Ölçüm (birim testi `fw_gateguard_orta_dusuk.php` (G17-c/d), master DB):
     * `projects` tablosunda **13 kolon** var ve **12'si framework tarafından
     * GERÇEKTEN tüketiliyor**:
     *   id           → `Bootstrap::setAppContext('project_id')`, `project_id()`
     *   project_key  → `project_key()`, `project_data('project_key')`
     *   project_name → `project_data('project_name')` (AuthHandler vb.)
     *   project_group→ `project_group()`
     *   domain       → `project_data('domain')` (RedirectTrait, RedirectManager)
     *   public_path  → `Paths.php:110`
     *   custom_path  → `ResolvesProjectConfigTrait` (yapılandırma dosya yolu)
     *   options      → `dashboard_prefix`, `disabled_options`
     *   version      → `app_version()` / `project_data('version')`
     *   created_at / updated_at → zaman damgası
     *
     * ⚠️ RAPORUN ÖNERİSİ UYGULANMADI: gateguard.md G-17
     * `project_key, id, status, license_key, expires_at` diyordu. Ölçüm
     * gösterdi ki (a) `projects` tablosunda **`expires_at` kolonu YOK** —
     * liste kullanılsaydı sorgu `Unknown column` ile patlar, `PreflightException`
     * fırlatır ve **18 site 500** dönerdi; (b) `domain`/`public_path`/`version`
     * düşseydi yönlendirme, `Paths` ve `AuthHandler` kırılırdı.
     *
     * `license_key` NEDEN YOK: satır `Bootstrap::setAppContext('project_data')`
     * ile uygulama bağlamına konur ve oradan **hiçbir yerde okunmaz**
     * (ölçüm: framework'te `license_key` yalnız `MasterProjectsRepository`'nin
     * kendi sorgusunda ve master yazma yollarında geçiyor; G-06'dan beri lisans
     * kararı `licences` tablosundan geliyor). Yani raporun "sızıntı" dediği
     * değer bu satırda taşınmıyor; liste onu da dışarıda bırakır.
     */
    private const PROJECT_DATA_COLUMNS = [
        'id', 'project_group', 'project_key', 'project_name', 'domain',
        'public_path', 'custom_path', 'status', 'options', 'version',
        'created_at', 'updated_at',
    ];

    /**
     * Fetch project status from Master Database 🏢
     *
     * @return array<string,mixed>|null Proje satırı ya da null.
     */
    public function fetchProjectData(string $projectKey): ?array
    {
        // [B-24 / FW-ALTYAPI-1] KAPSAM DISI SIZMANIN EN SICAK YOLU KAPANDI.
        //
        // OLÇÜM: `fetchProjectData()` HER İSTEKTE (boot sırasında) çağrılıyordu
        // ve `connection('database_master')` çağrısı, global aktif bağlantıyı
        // kalıcı olarak `database_master`'a SABİTLİYORDU. Bu, isteğin geri
        // kalanında gelen ham `$db->raw()` çağrılarının hedef veritabanını
        // belirlemesi demekti — yani proje verisinin master'a gitme riski.
        //
        // Yalnız okuma yapan bir metotta global durumu değiştirmenin anlamı
        // yoktur: `getProvider()` aynı PDO'yu verir, hiçbir şey sabitlemez.
        $masterPdo = \Rbn\Framework\Core\Database\Database::getInstance()
            ->getProvider('database_master')
            ->getPdo();

        // [G-17] Sutun listesi TEK KAYNAKTAN (self::PROJECT_DATA_COLUMNS);
        // SQL'e girmeden once `implode` edilir, degerler ESKI davranis gibi
        // `?` ile baglanir (kullanici girdisi SQL'e girmez).
        $sutunlar = implode(', ', array_map(
            static fn(string $c): string => '`' . $c . '`',
            self::PROJECT_DATA_COLUMNS
        ));

        $stmt = $masterPdo->prepare(
            "SELECT {$sutunlar} FROM projects WHERE project_key = ? LIMIT 1"
        );
        $stmt->execute([$projectKey]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Perform physical Heartbeat/Write test 💓
     */
    public function performHeartbeatTest(PDO $pdo): void
    {
        $table = 'z_sys_heartbeats';
        $keyField = 'heartbeat_key';
        $dummyValue = 'cli_preflight_' . time();

        $pdo->prepare("INSERT INTO {$table} ({$keyField}) VALUES (?) ON DUPLICATE KEY UPDATE {$keyField} = VALUES({$keyField})")
            ->execute([$dummyValue]);

        $pdo->prepare("DELETE FROM {$table} WHERE {$keyField} = ?")
            ->execute([$dummyValue]);
    }
}
