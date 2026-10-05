<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Migrations\Master;

use Rbn\Framework\Core\Services\Console\Base\BaseMigration;
use Rbn\Framework\Core\Support\Bridges\Helpers\Library\ProjectVersionResolver;
use Rbn\Framework\Core\Support\Bridges\Helpers\Library\Version;

/**
 * NormalizeProjectVersions - `projects.version` degerlerini A.B.C kuralina cevirir.
 *
 * PATRON KARARI (2026-10-05):
 *   - Proje surumunun TEK KAYNAGI master DB `projects.version` kolonudur.
 *   - Mevcut degerlerin TUMU yeni sistemin baslangic surumu `0.1.1`'e cekilir
 *     (deger gecersiz olsa da olmasa da: `1.0` iki parcadir ve kurala uymaz).
 *   - Kaybolan eski degerler `projects_version_backup` tablosunda SAKLANIR.
 *   - SEMA DEGISTIRILMEZ: `projects` tablosunun kolon tipi ve `DEFAULT` degeri
 *     AYNEN kalir. Sema degisimi "cok kritik" sayilir ve canliyi kirar;
 *     ayrica yeni proje kaydi yazan yol (`MasterProjectsService`) artik
 *     `Version::initial()` yaziyor.
 *
 * COZUMLEME: karar TEK cozucudan gelir (`ProjectVersionResolver` -> `Version`).
 * SQL tarafi yalniz `A.B.C` olmayan satirlari onceden eler (ucuz on filtre);
 * PHP tarafi satiri satiri karar verir. Boylece migration ile web okuyucusu
 * FARKLI bir kural uygulamaz.
 *
 * GERI ALINABILIRLIK: `down()` yedek tablodaki degerleri geri KOYAR ve yedek
 * tabloyu SILMEZ (kanit olarak birakilir).
 *
 * ETKI OLCUMLERI (eski degerler SIR DEGILDIR; sadece surum numarasidir):
 *   - Yerel master DB: 19 proje, hepsi `1.0` (gecersiz) -> 19 satir `0.1.1`.
 *   - `applications` tablosu: 0 satir; bu migration'a DOKUNMAZ.
 */
class NormalizeProjectVersions extends BaseMigration
{
    /** Kayit tablosunda gorunecek migration adi (tarih + konu). */
    public const NAME = '2026_10_05_000002_normalize_project_versions';

    /** Kaybolan eski degerlerin saklandigi yedek tablo. */
    public const BACKUP_TABLE = 'projects_version_backup';

    /** Son raporlanan sayilar (test/raporlama icin; sonucu degistirmez). */
    private static array $rapor = ['toplam' => 0, 'degisen' => 0];

    public function up(): void
    {
        $this->ensureBackupTable();

        // 1) Mevcut degerleri yedekle (INSERT IGNORE: migration iki kez
        //    calistirilsa bile ilk yedek BOZULMAZ).
        $this->db->rawExecute(
            "INSERT IGNORE INTO `" . self::BACKUP_TABLE . "` (`project_id`, `project_key`, `version`)
             SELECT p.`id`, p.`project_key`, p.`version`
             FROM `projects` p"
        );

        // 2) Yalniz `A.B.C` OLMAYAN satirlari oku (on filtre). Gecerli degerler
        //    OLDUGU GIBI kalir; hicbir satira dokunulmaz.
        $satirlar = $this->db->raw(
            "SELECT `id`, `version` FROM `projects`
             WHERE `version` IS NULL
                OR TRIM(`version`) = ''
                OR `version` NOT REGEXP '^(0|[1-9][0-9]*)\\\\.[0-9]\\\\.[0-9]$'"
        );

        $degisen = 0;
        foreach ($satirlar as $satir) {
            $mevcut = (string) ($satir['version'] ?? '');
            $hedef = ProjectVersionResolver::resolve($mevcut);

            // Ayni satiri iki kez yazmaz (koruma; `Version::isValid` zaten
            // eleme yapti ama karar tek merkezden gelir).
            if (Version::isValid(trim($mevcut)) && trim($mevcut) === $hedef) {
                continue;
            }

            $this->db->rawExecute('UPDATE `projects` SET `version` = ? WHERE `id` = ?', [$hedef, (int) $satir['id']]);
            $degisen++;
        }

        self::$rapor = ['toplam' => count($satirlar), 'degisen' => $degisen];

        error_log(sprintf(
            '[RBN] NormalizeProjectVersions: %d satirdan %d tanesi %s surumune cekildi (yedek: %s).',
            count($satirlar),
            $degisen,
            Version::INITIAL,
            self::BACKUP_TABLE
        ));
    }

    public function down(): void
    {
        // Yedekteki degerleri geri yaz (yedek tablo SILINMEZ: kanit kalir).
        $this->db->rawExecute(
            "UPDATE `projects` p
             JOIN `" . self::BACKUP_TABLE . "` b ON b.`project_id` = p.`id`
             SET p.`version` = b.`version`"
        );
    }

    /** Son `up()` cagrisinin sayaclari (yalniz raporlama; testler icin). */
    public static function rapor(): array
    {
        return self::$rapor;
    }

    /** Yedek tabloyu kurar (yoksa). `projects` tablosuna DOKUNMAZ. */
    private function ensureBackupTable(): void
    {
        $this->db->rawExecute("CREATE TABLE IF NOT EXISTS `" . self::BACKUP_TABLE . "` (
            `project_id` int NOT NULL,
            `project_key` varchar(50) NOT NULL,
            `version` varchar(20) DEFAULT NULL,
            `backed_up_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`project_id`),
            KEY `idx_projects_version_backup_key` (`project_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
}
