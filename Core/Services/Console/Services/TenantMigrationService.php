<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Console\Services;

use PDO;
use Rbn\Framework\Core\Database\Database;
use Rbn\Framework\Core\Database\Migrations\Tenant\TenantKeyMigration;
use Rbn\Framework\Core\System\Config\Definitions\DbProfiles\ProjectDbData;

/**
 * TenantMigrationService - Kiracı kolonu migration KOSUCUSU 🏃🔑
 *
 * [FW-ALTYAPI-2 / H] G2'nin koşu tarafı. Framework'ün `MigrationService`'i
 * **tek bir projenin** `database/migrations` dizinini tarar ve o anki bağlantı
 * üzerinde çalışır (ölçülen mekanik kısıt: FW-ALTYAPI-1 H §5.6 — "tek bir
 * migration TÜM proje DB'lerine birden koşamaz").
 *
 * Bu servis o kısıtı ÇÖZMEZ, **görünür kılar**: hedef veritabanı her koşuda
 * AÇIKÇA verilir, motor o veritabanında kendi tablo listesini işler.
 *
 * - `database_project` dışındaki profiller için de çalışır; ortak (common)
 *   veritabanının migration KOŞUCUSU YOKTUR — bu servis onu da kapsar
 *   (`$profile` parametresi).
 *
 * [ANAYASA §9] Hedef veritabanı adı framework'e YAZILMAZ; işletme tarafından
 * komut satırından gelir.
 */
final class TenantMigrationService
{
    private TenantKeyMigration $motor;
    private TenantAuditService $denetim;

    public function __construct(?TenantKeyMigration $motor = null)
    {
        $this->motor   = $motor ?? new TenantKeyMigration();
        $this->denetim = new TenantAuditService();
    }

    /**
     * Hedef tablo listesi — framework'ın tek doğruluk kaynağı.
     *
     * @return string[]
     */
    public function targetTables(): array
    {
        return ProjectDbData::TENANT_TABLES;
    }

    /**
     * Çalışma bağlantısı — framework'ün KENDİ bağlantısı, yenisi açılmaz.
 *
     * ÖNEMLİ: motor ve denetim `information_schema` ile tam nitelikli
 * (`` `db`.`tablo` ``) referanslar kullanır; MySQL aynı sunucudaki başka
 * şemaları da bu yolla görür. Bu yüzden hedef veritabanına BAĞLANMAK
     * gerekmez — ikinci bir bağlantı açmak hem gereksiz hem de kimlik bilgisi
     * katmanını ikinci bir yerden çağırmak olurdu. Bağlantı kimliği tek yerden
     * (`Database`) gelir; `secrets.php` bu yolun hiçbir adımında açılmaz.
     */
    public function pdo(): PDO
    {
        return Database::getInstance()->getPdo();
    }

    /**
     * Hedef veritabanında migration'ı koşar.
     *
     * @param  string[] $tables
     * @return array{status:string, expected:int, applied:int, dry_run:bool, steps:array, skipped:array}
     */
    public function run(PDO $pdo, string $database, array $tables, bool $dryRun = true): array
    {
        return $this->motor->apply($pdo, $database, $tables, $dryRun);
    }

    public function revert(PDO $pdo, string $database, array $tables): array
    {
        return $this->motor->revert($pdo, $database, $tables);
    }

    /**
     * Önce/sonra farkı: kolonu eksik olan hedefler ve literal `DEFAULT` taşıyan
     * kolonlar (denetimin YAZMAMASI için ayrı bir çağrı).
     *
     * @param  string[] $tables
     */
    public function report(PDO $pdo, string $database, array $tables): array
    {
        return [
            'database'        => $database,
            'without_column'  => $this->denetim->withoutColumn($pdo, $database, $tables),
            'literal_default' => $this->denetim->literalDefaultColumns($pdo, $database),
            'targets'         => $this->denetim->targetTables($pdo, $database, $tables),
        ];
    }
}