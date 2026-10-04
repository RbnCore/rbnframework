<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Console\Services;

use PDO;
use Rbn\Framework\Core\Base\Data\BaseModel;
use Rbn\Framework\Core\System\Config\Definitions\DbProfiles\ProjectDbData;

/**
 * TenantAuditService - Kiracı izolasyonu DENETİMİ (YALNIZ OKUR) 🔎📋
 *
 * [FW-ALTYAPI-2 / H] `rbn tenant:audit` komutunun motoru.
 *
 * TASARIM KURALI: bu sınıf **yalnız okur**. Kaynağında tek bir yazma fiili
 * (`INSERT/UPDATE/DELETE/ALTER/CREATE/DROP/TRUNCATE/REPLACE`) bulunmaz; birim
 * testi bunu metin taramasıyla sabitler. Denetim aracı veritabanını DEĞİŞTİRMEZ.
 *
 * [TASARIM §8-6] "Hangi tabloda hangi `project_key`'den kaç satır var, kolonu
 * olmayan tablolar hangileri" sorusu bugün YALNIZ elle `information_schema`
 * sorgusuyla cevaplanabiliyordu. Bu servis o cevabı kalıcı bir komuta çevirir.
 *
 * @see \Rbn\Framework\Core\Database\Migrations\Tenant\TenantKeyMigration
 */
final class TenantAuditService
{
    public function __construct(
        private readonly string $column = ProjectDbData::TENANT_COLUMN
    ) {
    }

    /**
     * Verilen veritabanlarının kiracı kolonu envanteri.
     *
     * @param  string[] $databases
     * @return array<int, array<string,mixed>>
     */
    public function auditDatabases(PDO $pdo, array $databases, array $tables): array
    {
        $rapor = [];

        foreach ($databases as $database) {
            $database = (string) $database;
            $rapor[] = [
                'database'    => $database,
                'with_column' => $this->withColumn($pdo, $database),
                'without'     => $this->withoutColumn($pdo, $database, $tables),
                'targets'     => $this->targetTables($pdo, $database, $tables),
                'literal_default' => $this->literalDefaultColumns($pdo, $database),
            ];
        }

        return $rapor;
    }

    /**
     * Kiracı kolonu OLAN tüm tablolar + kolonun satır dağılımı.
     *
     * @return array<int, array<string,mixed>>
     */
    public function withColumn(PDO $pdo, string $database): array
    {
        $sql = 'SELECT t.TABLE_NAME, c.COLUMN_TYPE, c.IS_NULLABLE, c.COLUMN_DEFAULT
                  FROM information_schema.COLUMNS c
                  JOIN information_schema.TABLES t
                    ON t.TABLE_SCHEMA = c.TABLE_SCHEMA AND t.TABLE_NAME = c.TABLE_NAME
                 WHERE c.TABLE_SCHEMA = ? AND c.COLUMN_NAME = ? AND t.TABLE_TYPE = \'BASE TABLE\'
              ORDER BY t.TABLE_NAME';

        $st = $pdo->prepare($sql);
        $st->execute([$database, $this->column]);
        $out = [];

        foreach ($st->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $table = (string) $row['TABLE_NAME'];
            $out[] = [
                'table'    => $table,
                'type'     => (string) $row['COLUMN_TYPE'],
                'nullable' => (string) $row['IS_NULLABLE'] === 'YES',
                'default'  => $row['COLUMN_DEFAULT'],
                'rows'     => $this->distribution($pdo, $database, $table),
                'null_rows' => $this->nullRowCount($pdo, $database, $table),
            ];
        }

        return $out;
    }

    /**
     * Kiracı kolonu OLMAYAN hedef tablolar (migration hedefi olup eksik olanlar).
     *
     * @param  string[] $tables
     * @return array<int, string>
     */
    public function withoutColumn(PDO $pdo, string $database, array $tables): array
    {
        $st = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND TABLE_TYPE = \'BASE TABLE\''
        );
        $stc = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );

        $eksik = [];
        foreach ($tables as $table) {
            $st->execute([$database, (string) $table]);
            if ((int) $st->fetchColumn() === 0) {
                continue; // tabloda yok; migration da ATLAYACAK
            }
            $stc->execute([$database, (string) $table, $this->column]);
            if ((int) $stc->fetchColumn() === 0) {
                $eksik[] = (string) $table;
            }
        }

        return $eksik;
    }

    /**
     * Hedef tablo × veritabanı ölçümü (migration kapsamının tam denetimi).
     *
     * @param  string[] $tables
     * @return array<int, array<string,mixed>>
     */
    public function targetTables(PDO $pdo, string $database, array $tables): array
    {
        $stt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND TABLE_TYPE = \'BASE TABLE\''
        );
        $stc = $pdo->prepare(
            'SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $sti = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.STATISTICS
              WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND SEQ_IN_INDEX = 1'
        );

        $out = [];
        foreach ($tables as $table) {
            $table = (string) $table;
            $stt->execute([$database, $table]);
            if ((int) $stt->fetchColumn() === 0) {
                continue;
            }
            $stc->execute([$database, $table, $this->column]);
            $col = $stc->fetch(\PDO::FETCH_ASSOC);
            $sti->execute([$database, $table, $this->column]);
            $out[] = [
                'table'    => $table,
                'column'   => $col ? true : false,
                'type'     => $col['COLUMN_TYPE'] ?? null,
                'nullable' => $col ? $col['IS_NULLABLE'] === 'YES' : null,
                'default'  => $col['COLUMN_DEFAULT'] ?? null,
                'indexed'  => ((int) $sti->fetchColumn()) > 0,
                'null_rows' => $col ? $this->nullRowCount($pdo, $database, $table) : 0,
                'distribution' => $col ? $this->distribution($pdo, $database, $table) : [],
            ];
        }

        return $out;
    }

    /**
     * Kolon düzeyinde SABİT (literal) `DEFAULT` taşıyan kolonlar.
     *
     * ÖLÇÜLEN SIZINTI: sabit `DEFAULT`, anahtarı yazmayan bir yazma yolunu
     * başka bir kiracıya bağlar. Tespit YALNIZ OKUR; düzeltme bu servisin
     * işi DEĞİLDİR (bkz. `TenantKeyMigration` ve canlı runbook).
     *
     * @return array<int, array{table:string, default:string}>
     */
    public function literalDefaultColumns(PDO $pdo, string $database): array
    {
        $st = $pdo->prepare(
            'SELECT c.TABLE_NAME, c.COLUMN_DEFAULT
               FROM information_schema.COLUMNS c
               JOIN information_schema.TABLES t
                 ON t.TABLE_SCHEMA = c.TABLE_SCHEMA AND t.TABLE_NAME = c.TABLE_NAME
              WHERE c.TABLE_SCHEMA = ? AND c.COLUMN_NAME = ?
                AND c.COLUMN_DEFAULT IS NOT NULL AND t.TABLE_TYPE = \'BASE TABLE\'
           ORDER BY t.TABLE_NAME'
        );
        $st->execute([$database, $this->column]);

        $out = [];
        foreach ($st->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $out[] = ['table' => (string) $row['TABLE_NAME'], 'default' => (string) $row['COLUMN_DEFAULT']];
        }
        return $out;
    }

    /**
     * Model beyan envanteri (G1): her somut model `scoped` değerini KENDİ
     * dosyasında yazıyor mu?
     *
     * `ReflectionProperty::getDeclaringClass()` sayesinde "beyan var mı"
     * sorusu ÇALIŞTIRMA YAPMADAN, yalnız sınıf meta verisinden cevaplanır —
     * kalıcı bir sayaç/ayar dosyası gerekmez.
     *
     * @param  string[] $modelClasses
     * @return array{declared:int, inherited:int, items:array<int,array<string,mixed>>}
     */
    public function modelDeclarations(array $modelClasses): array
    {
        $items = [];
        $declared = 0;
        $inherited = 0;

        foreach ($modelClasses as $class) {
            if (!class_exists($class) || !is_subclass_of($class, BaseModel::class)) {
                continue;
            }
            $rp = new \ReflectionProperty($class, 'scoped');
            $kendi = $rp->getDeclaringClass()->getName() === $class;
            $kendi ? $declared++ : $inherited++;

            $vars = (new \ReflectionClass($class))->getDefaultProperties();
            $items[] = [
                'model'    => $class,
                'table'    => $vars['table'] ?? null,
                'declared' => $kendi,
                'scoped'   => (bool) ($vars['scoped'] ?? false),
            ];
        }

        return ['declared' => $declared, 'inherited' => $inherited, 'items' => $items];
    }

    /**
 * Model beyan envanteri — DOSYA düzeyinde (G1'in yetkili ölçümü).
 *
 * Reflection tabanlı `modelDeclarations()` yalnız o an autoload OLABİLEN
 * sınıfları sayar; proje modellerinin ad alanı her projede farklı olduğu
 * için CLI bağlamında hepsi yüklenmez. Bu yöntem dosyayı okur ve **hiçbir
 * sınıf yüklemez** — bu yüzden 81 somut modelin tamamını kapsar ve bozuk bir
 * model sınıfı komutu düşüremez.
 *
 * @param  string[] $modelFiles Depo köküne göreli yollar.
 * @return array{models:int, declared:int, missing:array<int,string>, inheritance:int}
 */
    public function declarationAuditByFile(string $workspace, array $modelFiles): array
    {
        $declared = 0;
        $inheritance = 0;
        $missing = [];

        foreach ($modelFiles as $rel) {
            $yol = $workspace . '/' . ltrim(str_replace('\\', '/', (string) $rel), '/');
            if (!is_file($yol)) {
                continue;
            }
            $src = (string) file_get_contents($yol);

            // Soyut sınıfın kendi varsayılanı sayılmaz (beyan değil, tanım).
            if (preg_match('/^\s*(?:abstract\s+)?class\s+BaseModel\b/m', $src)) {
                continue;
            }
            // Tablosuz model: kapsam alanı uygulanamaz.
            if (preg_match('/protected\s+\$table\s*=\s*null\s*;/', $src)) {
                continue;
            }
            if (!preg_match('/protected\s+\$table\s*=\s*[^;\r\n]/', $src)) {
                continue;
            }

            if (preg_match('/protected\s+bool\s+\$scoped\s*=\s*(?:true|false)\s*;/', $src)) {
                $declared++;
            } else {
                $inheritance++;
                $missing[] = (string) $rel;
            }
        }

        return [
            'models'     => $declared + $inheritance,
            'declared'   => $declared,
            'inheritance' => $inheritance,
            'missing'    => $missing,
        ];
    }

    /* ======================================================================
       ÖLÇÜM YARDIMCILARI
       ====================================================================== */

    /**
     * `project_key` değerine göre satır dağılımı.
     *
     * @return array<string, int>
     */
    public function distribution(PDO $pdo, string $database, string $table): array
    {
        try {
            $rows = $pdo->query(
                'SELECT COALESCE(`' . $this->column . '`, \'<NULL>\') AS k, COUNT(*) AS c
                   FROM `' . $database . '`.`' . $table . '`
               GROUP BY `' . $this->column . '`
               ORDER BY c DESC'
            )->fetchAll(\PDO::FETCH_KEY_PAIR);
        } catch (\Throwable $e) {
            return ['<HATA> ' . $e->getCode() => -1];
        }

        $out = [];
        foreach ($rows as $k => $c) {
            $out[(string) $k] = (int) $c;
        }
        return $out;
    }

    public function nullRowCount(PDO $pdo, string $database, string $table): int
    {
        try {
            return (int) $pdo->query(
                'SELECT COUNT(*) FROM `' . $database . '`.`' . $table . '`
                  WHERE `' . $this->column . '` IS NULL'
            )->fetchColumn();
        } catch (\Throwable $e) {
            return -1;
        }
    }
}