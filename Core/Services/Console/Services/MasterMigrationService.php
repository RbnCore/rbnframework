<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Console\Services;

use Rbn\Framework\Core\Database\Database;
use Rbn\Framework\Core\Database\Migrations\Master\CreateLicenceAndApplicationTables;

/**
 * MasterMigrationService - Master (sistem) veritabani migration motoru 🏛️🔑
 *
 * NEDEN AYRI? Mevcut `MigrationService` PROJE veritabanina gore calisir:
 *   - `Paths::project()->root('database/migrations')` (proje kokunde arar)
 *   - kayit tablosu `migrations` (baglanilan DB'de kurulur)
 * Master'da (`rbncore_master`) proje koku YOKTUR; bu yuzden master migration
 * dosyalari framework icinde (`Core\Database\Migrations\Master`) ve kayit tablosu
 * `master_migrations` olarak tutulur.
 *
 * KURALLAR:
 *   - ASLA otomatik calismaz: yalniz elle `rbn master:migrate [--rollback]`.
 *   - Yalniz master baglantisi (`database_master`) uzerinde calisir; cikista
 *     aktif baglanti onceki haline GERI ALINIR (cagiran bozulmaz).
 *   - Sira: migration adina gore sirali, uygulanmis olanlar tekrar calismaz.
 *
 * @see \Rbn\Framework\Core\Database\Migrations\Master\CreateLicenceAndApplicationTables
 */
class MasterMigrationService
{
    /** Uygulama kaydi tutulan tablo (master DB icinde). */
    public const RECORD_TABLE = 'master_migrations';

    /** Bekleyen migration listesi: [migration adi => sinif]. */
    private const MIGRATIONS = [
        CreateLicenceAndApplicationTables::NAME => CreateLicenceAndApplicationTables::class,
    ];

    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Bekleyen master migration'lari calistirir. ASLA otomatik cagrilmaz.
     */
    public function migrate(): array
    {
        return $this->onMasterConnection(function (): array {
            $this->ensureRecordTable();
            $applied = $this->appliedNames();
            $batch = $this->nextBatch();
            $migrated = [];

            foreach (self::MIGRATIONS as $name => $class) {
                if (in_array($name, $applied, true)) {
                    continue;
                }
                $migration = new $class();
                $migration->up();
                $this->db->rawExecute(
                    'INSERT INTO `' . self::RECORD_TABLE . '` (migration, batch) VALUES (?, ?)',
                    [$name, $batch]
                );
                $migrated[] = $name;
            }

            return $migrated
                ? ['status' => 'success', 'migrated' => $migrated]
                : ['status' => 'nothing_to_migrate'];
        });
    }

    /**
     * Son master migration batchini geri alir (yalniz bu isin tablolari kalkar).
     */
    public function rollback(): array
    {
        return $this->onMasterConnection(function (): array {
            $this->ensureRecordTable();
            $batch = $this->lastBatch();
            if (!$batch) {
                return ['status' => 'nothing_to_rollback'];
            }

            $rows = $this->db->raw(
                'SELECT migration FROM `' . self::RECORD_TABLE . '` WHERE batch = ? ORDER BY migration DESC',
                [$batch]
            );
            $rolledBack = [];

            foreach ($rows as $row) {
                $name = (string) ($row['migration'] ?? '');
                $class = self::MIGRATIONS[$name] ?? null;
                if ($class === null) {
                    // Kayit var ama sinif yok: kaydi silme, operatora bildir.
                    throw new \RuntimeException("Master migration sinifi bulunamadi: {$name}");
                }
                $migration = new $class();
                $migration->down();
                $this->db->rawExecute('DELETE FROM `' . self::RECORD_TABLE . '` WHERE migration = ?', [$name]);
                $rolledBack[] = $name;
            }

            return ['status' => 'success', 'rolled_back' => $rolledBack];
        });
    }

    /**
     * Uygulanmis master migration kayitlari (raporlama icin).
     */
    public function getStatus(): array
    {
        return $this->onMasterConnection(function (): array {
            $this->ensureRecordTable();
            return $this->db->raw('SELECT migration, batch FROM `' . self::RECORD_TABLE . '` ORDER BY migration DESC');
        });
    }

    /**
     * Lisans kopyalama ozeti: kac proje satiri yazildi, kaci bos anahtarliydi.
     * Anahtar DEGERLERI bilerek YAZILMAZ (sadece adet).
     */
    public function licenceCopyReport(): array
    {
        return $this->onMasterConnection(function (): array {
            $projects = (int) ($this->db->rawFirst('SELECT COUNT(*) AS adet FROM `projects`')['adet'] ?? 0);
            // Migration henuz calismadiysa `licences` tablosu yoktur: hata degil, 0.
            $copied = $this->tableExists('licences')
                ? (int) ($this->db->rawFirst(
                    "SELECT COUNT(*) AS adet FROM `licences` WHERE subject_type = 'project'"
                )['adet'] ?? 0)
                : 0;
            $bosAnahtar = (int) ($this->db->rawFirst(
                "SELECT COUNT(*) AS adet FROM `projects` WHERE `license_key` IS NULL OR TRIM(`license_key`) = ''"
            )['adet'] ?? 0);

            return ['projects' => $projects, 'copied' => $copied, 'empty_key' => $bosAnahtar];
        });
    }

    /** Master DB'de tablo var mi? (yoksa sorgu patlamasin diye). */
    protected function tableExists(string $table): bool
    {
        $row = $this->db->rawFirst(
            'SELECT COUNT(*) AS adet FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = ?',
            [$table]
        );
        return ((int) ($row['adet'] ?? 0)) > 0;
    }

    /** Uygulama kayit tablosunu master DB'de kurar (yoksa). */
    protected function ensureRecordTable(): void
    {
        $this->db->rawExecute("CREATE TABLE IF NOT EXISTS `" . self::RECORD_TABLE . "` (
            id int NOT NULL AUTO_INCREMENT,
            migration varchar(190) NOT NULL,
            batch int NOT NULL,
            executed_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_master_migrations_migration (migration)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    /** Ad daha once uygulanmis mi? */
    protected function appliedNames(): array
    {
        $rows = $this->db->raw('SELECT migration FROM `' . self::RECORD_TABLE . '`');
        return array_map(static fn(array $r): string => (string) ($r['migration'] ?? ''), $rows);
    }

    protected function nextBatch(): int
    {
        $max = $this->db->rawFirst('SELECT MAX(batch) AS enbuyuk FROM `' . self::RECORD_TABLE . '`');
        return ((int) ($max['enbuyuk'] ?? 0)) + 1;
    }

    protected function lastBatch(): ?int
    {
        $max = $this->db->rawFirst('SELECT MAX(batch) AS enbuyuk FROM `' . self::RECORD_TABLE . '`');
        return !empty($max['enbuyuk']) ? (int) $max['enbuyuk'] : null;
    }

    /**
     * Islemi master baglantisi uzerinde yurutur, cikista aktif baglantiyi
     * onceki haline geri alir (singleton oldugu icin sizinti olmaz).
     */
    protected function onMasterConnection(callable $islem): mixed
    {
        // [B-24 · FW-KARAR-2-B] Save/restore deseni ConnectionTrait'e taşındı
        // (connectionScoped). DAVRANIS BIREBIR AYNI: aynı hedef ad, aynı
        // `finally` ile geri alma, istisna yutulmuyor, imza korundu.
        return $this->db->connectionScoped('database_master', $islem);
    }
}
