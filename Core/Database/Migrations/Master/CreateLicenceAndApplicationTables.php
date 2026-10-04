<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Migrations\Master;

use Rbn\Framework\Core\Services\Console\Base\BaseMigration;

/**
 * CreateLicenceAndApplicationTables - Merkezi Lisans + Uygulama tablolari 🔑📦🏛️
 *
 * PATRON KARARI (kesin):
 *   - TAM 3 TABLO: `projects` (var) + `applications` (yeni) + `licences` (yeni).
 *     `applications` ve `licences` TEK tabloda hem proje hem uygulama icin.
 *   - EK TABLO YOK: `application_releases`, `application_installations`,
 *     `application_audit_log`, `application_licenses` YASAK.
 *   - `projects` tablosundan HICBIR sutun KALDIRILMAZ (`version`, `license_key` durur).
 *
 * Bu migration `projects.license_key` degerlerini `licences` tablosuna KOPYALAR
 * (tasima degil kopyala: mevcut kod `projects.license_key` okumaya devam eder).
 *
 * Kopyalama KURALI (PATRON KARARI 2026-10-02 — "hepsi LIFETIME olacak"):
 *   tier   : HER mevcut proje -> 'LIFETIME' (anahtar ONEKI TAHMINI YOK;
 *            'RBN-LIFETIME-' / 'RBN-FREE-' / 'FREE-OSS' ayrimi ve ELSE->PRO KALDIRILDI)
 *   status : 'active' (bakim modu 'maintenance' ve 'suspended' da lisans durumu DEGILDIR;
 *            proje askiya alma kapıda `projects.status` uzerinden ayrica denetlenir)
 *   expires_at : NULL (suresiz)
 *   notes  : 'migration:existing-project' (satirin migration'dan geldiginin isaretci)
 *   Bos/NULL anahtarlara satir YAZILMAZ; adet raporlanir.
 *
 * `INSERT IGNORE` + UNIQUE(licence_key): migration iki kez calistirilsa bile
 * ayni anahtar ikinci kez YAZILMAZ (kopyalama kendini tekrar etmez).
 *
 * TEKIL KURAL (Y-2): `uq_licences_subject (subject_type, subject_id)` UNIQUE'dir.
 * Bir ozneye (proje veya uygulama) EN FAZLA bir lisans satiri yazilabilir; aksi
 * halde `findForSubject()` hangi satiri okuyacagini veritabani belirler ve
 * `licences` tablosunun okunmasi belirsizlasir. `issue()` de ayni ozneye ikinci
 * kaydi yazmayi reddeder; bu yuzden UNIQUE anahtar son savunma hattidir.
 */
class CreateLicenceAndApplicationTables extends BaseMigration
{
    /** Kayit tablosunda gorunecek migration adi (tarih + konu). */
    public const NAME = '2026_10_02_000001_create_licence_and_application_tables';

    public function up(): void
    {
        $this->db->rawExecute($this->applicationsDdl());
        $this->db->rawExecute($this->licencesDdl());
        $this->copyProjectLicences();
    }

    public function down(): void
    {
        // YALNIZ bu migration'in yarattigi iki tablo; `projects`e DOKUNMAZ.
        $this->db->rawExecute('DROP TABLE IF EXISTS `licences`');
        $this->db->rawExecute('DROP TABLE IF EXISTS `applications`');
    }

    /**
     * Mevcut proje lisanslarini merkezi tabloya kopyalar.
     *
     * KURAL (patron karari 2026-10-02): anahtar ONEKINDEN kademe TAHMINI YAPILMAZ.
     * Satir sayisi degismez (mevcut projelerin sayisi kadar satir yazilir), yalniz
     * her satir LIFETIME + active + suresiz olur. Bos/NULL anahtar atlanir.
     */
    protected function copyProjectLicences(): void
    {
        $this->db->rawExecute(
            "INSERT IGNORE INTO `licences`
                (`licence_key`, `subject_type`, `subject_id`, `tier`, `status`, `expires_at`, `max_activations`, `notes`, `created_at`, `updated_at`)
             SELECT p.`license_key`,
                'project',
                p.`id`,
                'LIFETIME',
                'active',
                NULL,
                1,
                'migration:existing-project',
                NOW(),
                NOW()
             FROM `projects` p
             WHERE p.`license_key` IS NOT NULL AND TRIM(p.`license_key`) <> ''"
        );
    }

    /** `applications` tablosunun DDL'i (urun surumu burada). */
    protected function applicationsDdl(): string
    {
        return "CREATE TABLE IF NOT EXISTS `applications` (
            `id` int NOT NULL AUTO_INCREMENT,
            `app_key` varchar(100) NOT NULL,
            `name` varchar(150) NOT NULL,
            `platform` enum('windows','macos','linux') NOT NULL DEFAULT 'windows',
            `channel` enum('stable','beta') NOT NULL DEFAULT 'stable',
            `status` enum('active','passive','retired') NOT NULL DEFAULT 'active',
            `current_version` varchar(20) DEFAULT NULL,
            `download_url` varchar(255) DEFAULT NULL,
            `sha256` char(64) DEFAULT NULL,
            `min_version` varchar(20) DEFAULT NULL,
            `description` text,
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_applications_app_key` (`app_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    }

    /** `licences` tablosunun DDL'i (TEK lisans tablosu; surum kolonu YOK). */
    protected function licencesDdl(): string
    {
        return "CREATE TABLE IF NOT EXISTS `licences` (
            `id` int NOT NULL AUTO_INCREMENT,
            `licence_key` varchar(100) NOT NULL,
            `subject_type` enum('project','application') NOT NULL,
            `subject_id` int NOT NULL,
            `tier` enum('FREE','LIFETIME','PRO') NOT NULL DEFAULT 'FREE',
            `status` enum('active','suspended','revoked','expired') NOT NULL DEFAULT 'active',
            `expires_at` datetime DEFAULT NULL,
            `max_activations` int NOT NULL DEFAULT 1,
            `device_hash` varchar(128) DEFAULT NULL,
            `notes` text,
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_licences_licence_key` (`licence_key`),
            UNIQUE KEY `uq_licences_subject` (`subject_type`,`subject_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    }
}
