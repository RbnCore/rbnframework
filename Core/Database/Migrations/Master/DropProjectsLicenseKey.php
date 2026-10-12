<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Migrations\Master;

use Rbn\Framework\Core\Services\Console\Base\BaseMigration;

/**
 * DropProjectsLicenseKey - `projects.license_key` sutununu kaldirir 🔑
 *
 * PROJE SAHİBİ KARARI (2026-10-10, FW-LISANS-3): lisansin TEK kaynagi `licences`
 * tablosudur; eski kopya sutun kaldirilir.
 *
 * GUVENLIK: sutun dusmeden once, `licences` tablosunda karsiligi olmayan
 * dolu anahtarlar oraya KOPYALANIR (LIFETIME/active; `INSERT IGNORE` ile
 * tekrar calistirilabilir). Sutun yoksa hicbir sey yapmaz (idempotent).
 *
 * GERI ALMA: `down()` sutunu eski tanimiyla (varchar(100) NULL) geri koyar ve
 * degerleri `licences` kaydindan doldurur.
 */
class DropProjectsLicenseKey extends BaseMigration
{
    /** Kayit tablosunda gorunecek migration adi (tarih + konu). */
    public const NAME = '2026_10_10_000003_drop_projects_license_key';

    public function up(): void
    {
        if (!$this->columnExists()) {
            return;
        }

        $this->db->rawExecute(
            "INSERT IGNORE INTO `licences`
                (`licence_key`, `subject_type`, `subject_id`, `tier`, `status`, `expires_at`, `max_activations`, `notes`, `created_at`, `updated_at`)
             SELECT p.`license_key`, 'project', p.`id`, 'LIFETIME', 'active', NULL, 1, 'migration:drop-license-key', NOW(), NOW()
             FROM `projects` p
             WHERE p.`license_key` IS NOT NULL AND TRIM(p.`license_key`) <> ''
               AND NOT EXISTS (
                   SELECT 1 FROM `licences` l WHERE l.`subject_type` = 'project' AND l.`subject_id` = p.`id`
               )"
        );

        $this->db->rawExecute('ALTER TABLE `projects` DROP COLUMN `license_key`');
    }

    public function down(): void
    {
        if ($this->columnExists()) {
            return;
        }

        $this->db->rawExecute('ALTER TABLE `projects` ADD COLUMN `license_key` varchar(100) NULL DEFAULT NULL AFTER `version`');
        $this->db->rawExecute(
            "UPDATE `projects` p
             JOIN `licences` l ON l.`subject_type` = 'project' AND l.`subject_id` = p.`id`
             SET p.`license_key` = l.`licence_key`"
        );
    }

    /** `projects.license_key` var mi? */
    private function columnExists(): bool
    {
        $row = $this->db->rawFirst(
            "SELECT COUNT(*) AS adet FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = 'projects' AND column_name = 'license_key'"
        );

        return ((int) ($row['adet'] ?? 0)) > 0;
    }
}
