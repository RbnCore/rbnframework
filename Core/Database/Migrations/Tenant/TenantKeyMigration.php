<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Migrations\Tenant;

use PDO;
use Rbn\Framework\Core\System\Config\Definitions\DbProfiles\ProjectDbData;

/**
 * TenantKeyMigration - Kiracı (`project_key`) kolonunun EKLEYİCİ şema motoru 🔑🏗️
 *
 * [FW-ALTYAPI-2 / H] Tasarım: `docs/agent-results/FW-ALTYAPI-1-H-team member.md`
 * §5 (ekleyici migration) + §7-G2 (migration motoru).
 *
 * ---------------------------------------------------------------------------
 * SINIRLAR (kodda zorlanır, açıklamaya bırakılmaz)
 * ---------------------------------------------------------------------------
 * 1. **YALNIZ EKLEYİCİ.** Bu sınıf hiçbir koşulda mevcut kolonu DÜŞÜRMEZ,
 *    mevcut kolonun TİPİNİ/DEĞERLERİNİ değiştirmez, satır silmez/güncellemez.
 *    Geri alma `revert()` ile AYRI ve açık çağrılır.
 * 2. **İDEMPOTENT.** Kolon zaten varsa hiçbir `ALTER` üretilmez; durum
 *    `idempotent` olur. Aynı migration ikinci kez koşulabilir.
 * 3. **KAPSAM DIŞI TABLOLAR DOKUNULMAZ.** Yalnız `$tables` içindeki adlar
 *    işlenir. `asw_*` yasağı buraya YAZILMAZ — liste kapsamı zaten dışarıda
 *    bırakır (bkz. `ProjectDbData::TENANT_TABLES`).
 * 4. **DRY-RUN SAYACI ZORUNLU.** `apply(..., dryRun: true)` hiçbir `ALTER`
 *    çalıştırmaz, yalnız "kaç işlem yapılırdı" sayısını döner. Böylece
 *    operatör etkiyi ÖNCE görür.
 * 5. **BOŞ ETKİ YUTULMAZ.** Beklenen işlem > 0 iken uygulanan işlem 0 ise
 *    durum `no_effect` olarak işaretlenir ve çağıran bunu görmek ZORUNDADIR
 *    (yanlışlıkla "başarılı" sayılmasın).
 *
 * ---------------------------------------------------------------------------
 * TİP KARARLARI (geçici şemada ölçülmüştür, FW-ALTYAPI-1 H §5.3)
 * ---------------------------------------------------------------------------
 *  - `varchar(64)` — ölçülen en uzun beyan 15 karakter; indeks maliyeti makul.
 *  - `NULL` + `DEFAULT NULL` — kolon `NOT NULL` yapılırsa NULL satır yüzünden
 *    MySQL **1138** ile reddediyor (ölçüldü). Varsayılan değer YOK: sabit bir
 *    `DEFAULT 'kiraci'` sızıntı kaynağıdır (ölçüldü: 30 kolonda sabit `DEFAULT`
 *    var, çoğu yanlış kiracıyı gösteriyor).
 *  - indeks **tek kolonlu ve NON-UNIQUE** — ölçüldü: iki kiracıda aynı
 *    başlık/slug mevcut, tek kolonlu `UNIQUE` patlıyor.
 *  - `AUTO_INCREMENT` birincil anahtar **DEĞİŞTİRİLMEZ** — birleşik PK
 *    `PRIMARY KEY (project_key, id)` MySQL **1075** ile reddediliyor (ölçüldü).
 *
 * @see \Rbn\Framework\Core\System\Config\Definitions\DbProfiles\ProjectDbData
 */
final class TenantKeyMigration
{
    /** Durum: hiçbir işlem gerekmiyor (kolonlar zaten yerinde). */
    public const STATUS_IDEMPOTENT = 'idempotent';

    /** Durum: yalnız ölçüldü, şemaya dokunulmadı. */
    public const STATUS_DRY_RUN = 'dry_run';

    /** Durum: işlemler uygulandı. */
    public const STATUS_APPLIED = 'applied';

    /** Durum: beklenen işlem vardı ama HİBİR ŞEY yazıldı — incelenmeli. */
    public const STATUS_NO_EFFECT = 'no_effect';

    /** Durum: veri çakışması nedeniyle işlem YAPILMADI (veri bütünlüğü). */
    public const STATUS_SKIPPED_CONFLICT = 'skipped_conflict';

    /** Bu sınıfın ürettiği UNIQUE indeks adı ön eki. */
    public const UNIQUE_INDEX_PREFIX = 'uq_';

    public function __construct(
        private readonly string $column = ProjectDbData::TENANT_COLUMN,
        private readonly string $columnType = ProjectDbData::TENANT_COLUMN_TYPE
    ) {
    }

    /**
     * Kolonun `CREATE TABLE` parçası — elle kurulan geçici tablolarda da aynı
     * tanım kullanılır (motor ile hedef tablo arasında şema farkı oluşmaz).
     */
    public function columnDefinition(): string
    {
        return '`' . $this->column . '` ' . $this->columnType
            . (ProjectDbData::TENANT_COLUMN_NULLABLE ? ' NULL' : ' NOT NULL')
            . ' DEFAULT NULL';
    }

    /**
     * Bir veritabanında hedef tabloların DURUMUNU ölçer (yalnız SELECT).
     *
     * @param  string[] $tables
     * @return array{schema:string, items:array<int, array<string,mixed>>}
     */
    public function plan(PDO $pdo, string $database, array $tables): array
    {
        $items = [];

        foreach ($tables as $table) {
            $table = (string) $table;
            $items[] = [
                'table'  => $table,
                'exists' => $this->tableExists($pdo, $database, $table),
                'column' => $this->columnState($pdo, $database, $table),
            ];
        }

        return ['schema' => $database, 'items' => $items];
    }

    /**
     * Kolon + indeks ekler (ekleyici, idempotent, geri alınabilir).
     *
     * @param  string[] $tables
     * @return array{status:string, expected:int, applied:int, dry_run:bool, steps:array<int,array<string,mixed>>, skipped:array<int,string>}
     */
    public function apply(PDO $pdo, string $database, array $tables, bool $dryRun = false): array
    {
        $steps = [];
        $skipped = [];
        $applied = 0;
        $expected = 0;

        foreach ($tables as $table) {
            $table = (string) $table;

            if (!$this->tableExists($pdo, $database, $table)) {
                $skipped[] = $table . ':veritabaninda_yok';
                continue;
            }

            // --- 1) KOLON ---
            $state = $this->columnState($pdo, $database, $table);
            if ($state === null) {
                $expected++;
                $sql = 'ALTER TABLE ' . $this->ref($database, $table) . ' ADD COLUMN '
                    . $this->columnDefinition();
                $steps[] = ['table' => $table, 'kind' => 'add_column', 'sql' => $sql];
                if (!$dryRun) {
                    $pdo->exec($sql);
                    $applied++;
                }
                $state = 'yeni';
            }

            // --- 2) TEK KOLONLU NON-UNIQUE İNDEKS ---
            if (!$this->indexExists($pdo, $database, $table)) {
                $expected++;
                $name = $this->indexName($table);
                $sql = 'ALTER TABLE ' . $this->ref($database, $table) . ' ADD INDEX `'
                    . $name . '` (`' . $this->column . '`)';
                $steps[] = ['table' => $table, 'kind' => 'add_index', 'sql' => $sql];
                if (!$dryRun) {
                    $pdo->exec($sql);
                    $applied++;
                }
            }
        }

        return [
            'status'   => $this->status($expected, $applied, $dryRun),
            'expected' => $expected,
            'applied'  => $applied,
            'dry_run'  => $dryRun,
            'steps'    => $steps,
            'skipped'  => $skipped,
        ];
    }

    /**
     * Geri alma: önce indeks, sonra kolon. Veri kaybı YOKTUR — kolon
     * ekleyici olduğu için içindeki veri zaten yazma yolları tarafından
     * doldurulmuş olabilir; bu yüzden çağıran bu işlemi ETKİNCE bildirmelidir.
     *
     * @param  string[] $tables
     * @return array{reverted:array<int,string>, skipped:array<int,string>}
     */
    public function revert(PDO $pdo, string $database, array $tables): array
    {
        $reverted = [];
        $skipped = [];

        foreach ($tables as $table) {
            $table = (string) $table;

            if (!$this->tableExists($pdo, $database, $table)) {
                $skipped[] = $table . ':veritabaninda_yok';
                continue;
            }

            // İndeks adı TAHMİN EDİLMEZ; kolon üzerindeki GERÇEK adlar
            // okunur. (Ölçülen hata: `CREATE TABLE ... LIKE` ile türetilen
            // tabloda indeks adı farklıdır; adı hesaplayan geri alma
            // MySQL 1091 "Can't DROP" hatasıyla PATLARDI.)
            foreach ($this->indexNames($pdo, $database, $table) as $indexName) {
                $pdo->exec('ALTER TABLE ' . $this->ref($database, $table)
                    . ' DROP INDEX `' . $indexName . '`');
            }

            if ($this->columnState($pdo, $database, $table) !== null) {
                $pdo->exec('ALTER TABLE ' . $this->ref($database, $table) . ' DROP COLUMN `'
                    . $this->column . '`');
                $reverted[] = $table;
            } else {
                $skipped[] = $table . ':kolon_yok';
            }
        }

        return ['reverted' => $reverted, 'skipped' => $skipped];
    }

    /* ======================================================================
       ÖLÇÜM YARDIMCILARI (yalnız SELECT)
       ====================================================================== */

    public function tableExists(PDO $pdo, string $database, string $table): bool
    {
        $st = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND TABLE_TYPE = \'BASE TABLE\''
        );
        $st->execute([$database, $table]);
        return ((int) $st->fetchColumn()) > 0;
    }

    /**
     * Kolonun durumu; yoksa `null`.
     *
     * @return array{type:string, nullable:string, default:?string, key:string}|null
     */
    public function columnState(PDO $pdo, string $database, string $table): ?array
    {
        $st = $pdo->prepare(
            'SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_KEY
               FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $st->execute([$database, $table, $this->column]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return [
            'type'     => (string) $row['COLUMN_TYPE'],
            'nullable' => (string) $row['IS_NULLABLE'],
            'default'  => $row['COLUMN_DEFAULT'],
            'key'      => (string) $row['COLUMN_KEY'],
        ];
    }

    /** Kolonun İLK konumunda bir indeks var mı (tek kolonlu yeterli). */
    public function indexExists(PDO $pdo, string $database, string $table): bool
    {
        return $this->indexNames($pdo, $database, $table) !== [];
    }

    /**
     * Kiracı kolonunun ilk konumunda bulunan indekslerin GERÇEK adları.
     *
     * Geri alma bunu kullanır: ad tahmin etmek (ör. `ix_<tablo>_project_key`)
     * güvenli DEĞİLDİR — tablo önceden farklı adlı bir indeks taşıyor olabilir
     * (ölçülen hata: MySQL 1091).
     *
     * @return string[]
     */
    public function indexNames(PDO $pdo, string $database, string $table): array
    {
        $st = $pdo->prepare(
            'SELECT DISTINCT INDEX_NAME FROM information_schema.STATISTICS
              WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND SEQ_IN_INDEX = 1'
        );
        $st->execute([$database, $table, $this->column]);

        return array_map('strval', $st->fetchAll(PDO::FETCH_COLUMN));
    }

    /* ======================================================================
       UNIQUE (project_key, <slug>) — A-26
       ======================================================================
       KÖK NEDEN (ölçülen hata, FW-CANLI-ONCESI/Z): UNIQUE indeks tespiti
       `COLUMN_NAME IN (project_key, slug)` filtresiyle yapılıyordu. ÜÇ kolonlu
       bir UNIQUE (project_key, type, slug) indeksi de bu filtreyle İKİ satır
       döndürür ve "tam olarak UNIQUE (project_key, slug) var" sanılıyordu.
       Sonuç: bir içerik kategorileri tablosuna iki kolonlu UNIQUE
       HİÇBİR ZAMAN eklenmedi. Burada indekslerin TÜM kolonları (SEQ_IN_INDEX
       sırasıyla) okunur ve kolon listesi BİREBİR karşılaştırılır.
       -------------------------------------------------------------------- */

    /**
     * Tablodaki UNIQUE indekslerin adı → sıralı kolon listesi haritası.
     *
     * @return array<string, string[]>
     */
    public function uniqueIndexMap(PDO $pdo, string $database, string $table): array
    {
        $st = $pdo->prepare(
            'SELECT INDEX_NAME, SEQ_IN_INDEX, COLUMN_NAME
               FROM information_schema.STATISTICS
              WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND NON_UNIQUE = 0
              ORDER BY INDEX_NAME, SEQ_IN_INDEX'
        );
        $st->execute([$database, $table]);

        $map = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $map[(string) $r['INDEX_NAME']][(int) $r['SEQ_IN_INDEX']] = (string) $r['COLUMN_NAME'];
        }
        foreach ($map as $ad => $kolonlar) {
            ksort($kolonlar);
            $map[$ad] = array_values($kolonlar);
        }

        return $map;
    }

    /**
     * Kolon listesi BİREBİR `$kolonlar` ile aynı olan bir UNIQUE indeks var mı?
     *
     * Sıra duyarlıdır ve kolon SAYISI da karşılaştırılır: üç kolonlu bir indeks
     * iki kolonlu sanılamaz.
     *
     * @param string[] $kolonlar
     */
    public function hasUniqueIndex(PDO $pdo, string $database, string $table, array $kolonlar): bool
    {
        $istenen = array_values(array_map('strval', $kolonlar));
        if ($istenen === []) {
            return false;
        }
        foreach ($this->uniqueIndexMap($pdo, $database, $table) as $sira) {
            if ($sira === $istenen) {
                return true;
            }
        }

        return false;
    }

    /**
     * `(project_key, <slug>)` çakışan GRUP sayısı (yalnız SELECT).
     *
     * NULL satırlar sayılmaz — UNIQUE bunları zaten engellemez.
     */
    public function duplicateGroupCount(PDO $pdo, string $database, string $table, string $slugColumn): int
    {
        $st = $pdo->prepare(
            'SELECT COUNT(*) FROM (SELECT `' . $this->column . '`, `' . $slugColumn . '`
                FROM ' . $this->ref($database, $table) . '
               WHERE `' . $this->column . '` IS NOT NULL AND `' . $slugColumn . '` IS NOT NULL
               GROUP BY `' . $this->column . '`, `' . $slugColumn . '`
              HAVING COUNT(*) > 1) x'
        );
        $st->execute();

        return (int) $st->fetchColumn();
    }

    /**
     * `UNIQUE (project_key, <slug>)` ekler — EKLEYİCİ ve idempotent.
     *
     * Zorlanan kurallar:
     *  1. Tablo/kolon yoksa atlanır (skipped).
     *  2. **ÇAKIŞMA > 0 ise ASLA eklenmez** (veri bütünlüğü).
     *  3. Zaten varsa hiçbir DDL üretilmez → `idempotent`.
     *  4. `dryRun` yalnız sayar, hiçbir `ALTER` çalıştırmaz.
     *
     * @return array{status:string, expected:int, applied:int, dry_run:bool,
     *               duplicate_groups:int, steps:array<int,array<string,mixed>>,
     *               skipped:array<int,string>}
     */
    public function applyUnique(PDO $pdo, string $database, string $table, string $slugColumn, bool $dryRun = false): array
    {
        $steps = [];
        $skipped = [];

        if (!$this->tableExists($pdo, $database, $table)) {
            return [
                'status' => self::STATUS_IDEMPOTENT, 'expected' => 0, 'applied' => 0,
                'dry_run' => $dryRun, 'duplicate_groups' => 0, 'steps' => $steps,
                'skipped' => [$table . ':veritabaninda_yok'],
            ];
        }
        if (!$this->columnExists($pdo, $database, $table, $slugColumn)) {
            return [
                'status' => self::STATUS_IDEMPOTENT, 'expected' => 0, 'applied' => 0,
                'dry_run' => $dryRun, 'duplicate_groups' => 0, 'steps' => $steps,
                'skipped' => [$table . ':slug_kolonu_yok'],
            ];
        }

        $beklenen = [$this->column, $slugColumn];

        if ($this->hasUniqueIndex($pdo, $database, $table, $beklenen)) {
            return [
                'status' => self::STATUS_IDEMPOTENT, 'expected' => 0, 'applied' => 0,
                'dry_run' => $dryRun, 'duplicate_groups' => 0, 'steps' => $steps,
                'skipped' => [$table . ':unique_zaten_var'],
            ];
        }

        $cakisma = $this->duplicateGroupCount($pdo, $database, $table, $slugColumn);
        if ($cakisma > 0) {
            return [
                'status' => self::STATUS_SKIPPED_CONFLICT, 'expected' => 0, 'applied' => 0,
                'dry_run' => $dryRun, 'duplicate_groups' => $cakisma, 'steps' => $steps,
                'skipped' => [$table . ':cakisma_' . $cakisma],
            ];
        }

        $sql = 'ALTER TABLE ' . $this->ref($database, $table) . ' ADD UNIQUE `'
            . $this->uniqueIndexName($table, $slugColumn) . '` (`' . $this->column
            . '`, `' . $slugColumn . '`)';
        $steps[] = ['table' => $table, 'kind' => 'add_unique', 'sql' => $sql];
        $applied = 0;
        if (!$dryRun) {
            $pdo->exec($sql);
            $applied = 1;
        }

        return [
            'status' => $this->status(1, $applied, $dryRun),
            'expected' => 1,
            'applied' => $applied,
            'dry_run' => $dryRun,
            'duplicate_groups' => 0,
            'steps' => $steps,
            'skipped' => $skipped,
        ];
    }

    /**
     * Geri alma: YALNIZ bu sınıfın kendi adlandırma deseninde ürettiği UNIQUE
     * indeksleri düşürür. Elle kurulmuş başka indekslere DOKUNMAZ.
     *
     * @return string[] düşürülen indeks adları
     */
    public function revertUnique(PDO $pdo, string $database, string $table, string $slugColumn): array
    {
        $dusenler = [];
        if (!$this->tableExists($pdo, $database, $table)) {
            return $dusenler;
        }
        $ad = $this->uniqueIndexName($table, $slugColumn);
        $map = $this->uniqueIndexMap($pdo, $database, $table);
        if (!isset($map[$ad]) || $map[$ad] !== [$this->column, $slugColumn]) {
            return $dusenler; // ad tutmuyor ya da kolon listesi farklı → dokunma
        }
        $pdo->exec('ALTER TABLE ' . $this->ref($database, $table) . ' DROP INDEX `' . $ad . '`');
        $dusenler[] = $ad;

        return $dusenler;
    }

    /** UNIQUE indeks adı (63 karakter sınırına güvenli kısaltma). */
    public function uniqueIndexName(string $table, string $slugColumn): string
    {
        $ad = self::UNIQUE_INDEX_PREFIX . $table . '_' . $this->column . '_' . $slugColumn;
        if (strlen($ad) <= 60) {
            return $ad;
        }

        return self::UNIQUE_INDEX_PREFIX . substr($table, 0, 30) . '_' . $slugColumn;
    }

    /** Kolon gerçekten var mı (yalnız ölçüm). */
    public function columnExists(PDO $pdo, string $database, string $table, string $column): bool
    {
        $st = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $st->execute([$database, $table, $column]);

        return ((int) $st->fetchColumn()) > 0;
    }

    /** İndeks adı (63 karakter sınırına güvenli kısaltma). */
    public function indexName(string $table): string
    {
        $ad = ProjectDbData::TENANT_INDEX_PREFIX . $table . '_' . $this->column;
        if (strlen($ad) <= 60) {
            return $ad;
        }
        return ProjectDbData::TENANT_INDEX_PREFIX . substr($table, 0, 40) . '_' . $this->column;
    }

    /* ======================================================================
       İÇ
       ====================================================================== */

    /**
     * Tam nitelikli tablo referansı: `` `veritabani`.`tablo` ``
     * (migration hedef veritabanı, işletme tarafından verilir).
     */
    private function ref(string $database, string $table): string
    {
        return '`' . $database . '`.`' . $table . '`';
    }

    /**
     * Durum. "Beklenen işlem vardı ama hiçbiri yazılmadı" durumu MUTLAK boş
     * yutulmaz — bu, kolonun zaten ekli olduğu hâlde yanlış tablo/veritabanı
     * hedeflendiğini ele verir.
     */
    private function status(int $expected, int $applied, bool $dryRun): string
    {
        if ($dryRun) {
            return self::STATUS_DRY_RUN;
        }
        if ($applied > 0) {
            return self::STATUS_APPLIED;
        }
        if ($expected === 0) {
            return self::STATUS_IDEMPOTENT;
        }
        return self::STATUS_NO_EFFECT;
    }
}
