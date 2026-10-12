<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Gatekeepers\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\System\Kernel\Bootstrap;
use Rbn\Framework\Core\Support\Exceptions\PreflightException;
use Rbn\Framework\Core\Services\Master\Data\MasterLicenceConfig;
use Rbn\Framework\Core\Services\Master\LicenceAccessRule;
use Rbn\Framework\Core\Services\Gatekeepers\Concerns\LogsGuardErrors;
use Rbn\Framework\Core\Services\Gatekeepers\Concerns\LogsLicenceWarnings;
use Rbn\Framework\Core\System\Discovery\Clusters\Logic\Definition\Definition;
use PDO;
use PDOException;

/**
 * DatabaseGuardHandler - The Logic Engine for DB Integrity 🛡️⚙️⚓
 *
 * RBN Framework: database preflight via DatabaseGuardProvider; security
 * logic and rule enforcement only. [G-18] Ham DB hata mesajı kullanıcıya
 * GÖSTERİLMEZ; `LogsGuardErrors` ile sunucu günlüğüne yazılır.
 *
 * ⚠️ SATIR SINIRI: `fw_licence_kapi.php` K6 bu dosyayı <= 250 satırda tutar
 * (gevşetilMEZ); günlük yardımcıları bu yüzden iki trait'te yaşar.
 */
class DatabaseGuardHandler extends BaseComponent
{
    use LogsGuardErrors;
    use LogsLicenceWarnings;

    /** @var array Shared cache for validated connection results 🧠 */
    private static array $validated = [];

    /**
     * Resolve and Validate Path for a Database context 🛰️
     */
    public function resolve(string $category, ?string $projectKey = null): string
    {
        $projectKey = $projectKey ?: (function_exists('active_project_key') ? active_project_key() : null);
        $cacheKey = $category . '_' . ($projectKey ?: 'default');

        // 🎼 RBN Framework: [DELEGATED AUTHORITY] 🏛️⚙️
        $path = \Rbn\Framework\Core\System\Config\Engine\Config\ConfigResolver::resolve($category, $projectKey);

        if (!isset(self::$validated[$cacheKey])) {
            self::$validated[$cacheKey] = true;
            $this->validateIntegrity($category, $path);
        }

        return $path;
    }

    /**
     * Orchestrate full integrity validation (Logic Orchestrator) 🩺🛡️
     */
    private function validateIntegrity(string $category, string $path): void
    {
        // 1. Physical File Check 📂
        // 🛡️ RBN Framework: [MASTER & COMMON IDENTITY BYPASS]
        // Master and Common DB configurations are static models, so we bypass the file existence check.
        if (!in_array($category, ['database_master', 'database_common']) && !file_exists($path)) {
            throw new PreflightException(
                "Veritabanı yapılandırma dosyası eksik!",
                "[{$category}] için [{$path}] dosyası bulunamadı."
            );
        }

        // 🎻 RBN Framework: The Bridge Pattern 🌁 (Physical Provider delegate)
        $provider = $this->provider('databaseGuard');

        // 2. Load & Validate Credentials 🧬
        $provider->loadCredentials($category, $path);

        // 3. Test Physical Connection 🔗
        try {
            $pdo = $provider->testConnection($category);
        } catch (PreflightException $e) {
            throw $e;
        } catch (PDOException $e) {
            $this->logGuardError('test_connection_failed', $category, $e);
            throw new PreflightException(
                "Veritabanı Bağlantı Başarısız! [{$category}]",
                "Veritabanına bağlanılamadı. Hata ayrıntısı için sunucu günlüklerine bakınız.",
                "Veritabanı sunucusunun ayakta ve erişilebilir olduğundan emin olun."
            );
        } catch (\Throwable $e) {
            $this->logGuardError('connection_engine_failed', $category, $e);
            throw new PreflightException(
                "Veritabanı Servis Hatası! [{$category}]",
                "Bağlantı motoru beklenmeyen bir iç hatayla karşılaştı. Hata ayrıntısı için sunucu günlüklerine bakınız.",
                "Konfigürasyonun veya Provider yapısının doğruluğunu kontrol edin."
            );
        }

        // 4. Structural Integrity Check 🧱
        $this->verifyTables($category, $pdo);

        // 5. Tenant Validation (Project Status) 🏢🛡️
        if ($category === 'database_project') {
            $this->checkProjectStatus($category, $path);
        }

        // 6. Practical Safety Check (Write Permissions) ✍️⚓
        $this->checkWritePermissions($category, $pdo);
    }

    /**
     * Logical Table Verifier 🛡️🧱
     */
    private function verifyTables(string $category, PDO $pdo): void
    {
        $requiredTables = Definition::get($category, 'REQUIRED_TABLES') ?? [];
        if (empty($requiredTables))
            return;

        try {
            $existing = $this->provider('databaseGuard')->fetchTableList($pdo);

            foreach ($requiredTables as $table) {
                if (!in_array($table, $existing)) {
                    throw new PreflightException(
                        "Tablo Okuma Hatası ({$category}) - Zorunlu tablo eksik: [{$table}]",
                        "Veritabanı şemasını kontrol edin veya migrasyonları çalıştırın."
                    );
                }
            }

            // 🎼 [AUTONOMOUS SYNC] 🏛️🛰️⚓ (yapı bütünlüğü doğrulandı)
        } catch (PDOException $e) {
            $this->logGuardError('table_list_failed', $category, $e);
            throw new PreflightException(
                "Tablo Okuma Hatası ({$category}) - Sorgu Başarısız: tablolar okunamadı (hata ayrıntısı sunucu günlüğünde).",
                "Veritabanı tablolarının okunabilir olduğundan emin olun."
            );
        }
    }

    /**
     * Project Status Guard: Validates project state via Master Provider 🏛️🛡️
     */
    private function checkProjectStatus(string $category, string $path): void
    {
        // 🎼 RBN Framework: [RBN Framework DISCOVERY SYNC] 🛰️🏛️⚓
        $projectData = Bootstrap::getAppContext('project_data');

        if (!$projectData) {
            $settings = file_exists($path) ? include $path : [];
            $projectKey = $settings['PROJECT_KEY'] ?? $settings['project_key'] ?? null;

            if (!$projectKey) {
                throw new PreflightException("Proje Hatası", "Yapılandırma dosyasında PROJECT_KEY bulunamadı.");
            }

            try {
                $projectData = $this->provider('databaseGuard')->fetchProjectData($projectKey);
            } catch (PDOException $e) {
                $this->logGuardError('project_status_failed', 'database_project', $e, ['project_key' => $projectKey]);
                throw new PreflightException("Master Doğrulama Hatası", "Proje durumu doğrulanırken Master DB hatası oluştu (hata ayrıntısı sunucu günlüğünde).");
            }
        }

        if (!$projectData) {
            throw new PreflightException("Proje Bilgisi Eksik", "Master kayıtlarında projeye ait bir veri bulunamadı.");
        }

        $projectKey = $projectData['project_key'] ?? 'unknown';

        // 🛡️ [LISANS KAPISI / FW-LICENCE-G · FW-LISANS-1] — karar DOĞRUDAN `licences`
        // tablosundan, kural `LicenceAccessRule`'da. (1) Geçerli (aktif, süresi
        // dolmamış) lisans kaydı YOKSA proje durumu ne olursa olsun erişim kapanır.
        // (2) Geçerli lisans olsa da kısıtsız (FREE) değilse `status` active olmalı.
        $kayit = $this->licenceRecord($projectData);

        if (!LicenceAccessRule::isValid($kayit)) {
            throw new \Rbn\Framework\Core\Support\Exceptions\ProjectSuspendedException(
                "Bu projenin geçerli bir lisansı bulunmuyor.",
                "Lisans tanımlanması veya yenilenmesi için sistem yöneticinizle iletişime geçin. (Key: {$projectKey})",
                "Lisans Gerekli"
            );
        }

        if (!LicenceAccessRule::isUnrestricted($kayit) && ($projectData['status'] ?? 'passive') !== 'active') {
            throw new \Rbn\Framework\Core\Support\Exceptions\ProjectSuspendedException(
                "Bu proje şuan için dondurulmuş veya pasif durumdadır.",
                "Hizmet almak için sistem yöneticinizle iletişime geçin. (Key: {$projectKey})",
                "Erişim Engellendi"
            );
        }

        // [RBN Framework] Identity Sealing 🧬🏛️⚓
        Bootstrap::setAppContext('project_id', (int) ($projectData['id'] ?? 0));
        Bootstrap::setAppContext('project_data', $projectData);
    }

    /**
     * Projenin `licences` kaydını okur (yok/okunamadı → null = kısıtlı) 🔑
     *
     * KURAL BURADA DEĞİL: tek tanım `LicenceAccessRule`. Burada yalnız kayıt
     * okunur, okuma hatası null'a indirgenir ve ANORMAL durum günlüğe yazılır.
     * Anahtar DEĞERİ hiçbir yere yazılmaz.
     */
    private function licenceRecord(array $projectData): ?array
    {
        $projectId = (int) ($projectData['id'] ?? 0);
        $kayit = null;

        if ($projectId > 0) {
            try {
                $kayit = $this->service('masterLicences')
                    ->forSubject(MasterLicenceConfig::SUBJECT_PROJECT, $projectId);
            } catch (\Throwable $e) {
                $this->licenceWarn('Lisans servisi okunamadı: ' . $e->getMessage());
            }
        }

        $satir = is_array($kayit) ? $kayit : null;

        // Rutin kısıtlama SESSİZDİR (B-04); anormal durum (kayıt yok/bozuk) bir kez yazılır.
        if (LicenceAccessRule::isAnomalous($satir)) {
            $this->licenceWarnOnce('Lisans kaydı okunamadı veya bozuk, kısıtlı sayıldı (project id=' . $projectId . ').');
        }

        return $satir;
    }

    /** @var bool Aynı süreçte anormal durum günlüğü yazıldı mı? (B-04) */
    // [G-18] `$anormalGunluklendi`, `licenceWarn()` ve `licenceWarnOnce()`
    // `Concerns\LogsLicenceWarnings` trait'ine TAŞINDI (bkz. sınıf docblock).

    /**
     * Permission Guard: Enforces write privileges ✍️🛡️⚓
     */
    private function checkWritePermissions(string $category, PDO $pdo): void
    {
        try {
            $this->provider('databaseGuard')->performHeartbeatTest($pdo);
        } catch (PDOException $e) {
            throw new PreflightException(
                "Yazma Yetkisi Hatası! [{$category}]",
                "Veritabanı kullanıcısının rbn_heartbeats tablosuna yazma yetkisi yok.",
                "Veritabanı izinlerini kontrol edin."
            );
        }
    }

}
