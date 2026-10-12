<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnFactory\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\Database\Database;

/**
 * FactorySchemaHandler - Fabrika tablolarını ilk kullanımda kurar 🧱
 *
 * Proje migration hattı (`database/migrations`) proje klasörüne yeni dizin gerektirdiği ve
 * paket tablosu projeden bağımsız olduğu için kurulum burada, YOKSA-OLUŞTUR olarak yapılır.
 * SchemaBuilder tablo oluşturamadığı için bu iki DDL (ve iş tablosuna yenileme kolonlarını ekleyen
 * tek ALTER) paketteki TEK ham SQL istisnasıdır; veri okuma/yazma sorgularının tamamı
 * Repository + query builder üzerindendir.
 */
class FactorySchemaHandler extends BaseComponent
{
    private static bool $done = false;

    public function ensure(): void
    {
        if (self::$done) {
            return;
        }
        $config = $this->handler('factoryConfig');
        $jobs = $config->table('jobs');
        $notices = $config->table('notices');
        $db = Database::getInstance();
        $db->connectionScoped('database_project', function () use ($db, $jobs, $notices): void {
            if (!$db->schema($jobs)->exists()) {
                $db->rawExecute($this->jobsDdl($jobs));
            } else {
                $this->upgradeJobs($db, $jobs);
            }
            if (!$db->schema($notices)->exists()) {
                $db->rawExecute($this->noticesDdl($notices));
            }
        });
        self::$done = true;
    }

    /**
     * Yenileme (renew) öncesi kurulmuş iş tablosuna EKLEYİCİ güncelleme (idempotent): `kind`, `renew_payload`,
     * ve yenileme işinin taslağı olmadığı için `draft_id` boş olabilir (benzersiz indeks NULL'ları tekrar saymaz).
     */
    private function upgradeJobs(Database $db, string $table): void
    {
        if ($this->missingColumns($db, $table) === []) {
            return;
        }
        // Eşzamanlı ilk istekler aynı ALTER'ı çalıştırmasın: kilit altında yeniden bakılır
        $this->handler('factoryLock')->synchronized('schema-upgrade:' . $table, function () use ($db, $table): void {
            $this->applyJobsUpgrade($db, $table);
        });
    }

    /** @return string[] eksik yenileme kolonları */
    private function missingColumns(Database $db, string $table): array
    {
        return array_values(array_diff(['kind', 'renew_payload', 'renew_post_id'], $db->schema($table)->getColumnListing()));
    }

    private function applyJobsUpgrade(Database $db, string $table): void
    {
        $columns = $db->schema($table)->getColumnListing();
        if (!in_array('kind', $columns, true)) {
            $db->rawExecute("ALTER TABLE `{$table}`
              MODIFY draft_id int DEFAULT NULL,
              ADD COLUMN kind enum('new','renew') NOT NULL DEFAULT 'new' AFTER content_type,
              ADD COLUMN renew_payload mediumtext DEFAULT NULL AFTER missing_reason");
        }
        if (!in_array('renew_post_id', $columns, true)) {
            // Tekrar garantisi: renew işinde yenilenen yazı bu kolona da yazılır; benzersiz indeks bir yazıya
            // ikinci renew işi açılmasını VERİTABANI düzeyinde engeller (yeni yazı işlerinde NULL: indeks saymaz).
            $db->rawExecute("ALTER TABLE `{$table}`
              ADD COLUMN renew_post_id int DEFAULT NULL AFTER post_id,
              ADD UNIQUE KEY uq_factory_renew_post (project_key, content_type, renew_post_id)");
            $db->rawExecute("UPDATE `{$table}` SET renew_post_id = post_id WHERE kind = 'renew'");
        }
    }

    private function jobsDdl(string $table): string
    {
        return "CREATE TABLE `{$table}` (
          id int NOT NULL AUTO_INCREMENT,
          project_key varchar(50) NOT NULL,
          job_no varchar(64) NOT NULL,
          draft_id int DEFAULT NULL,
          post_id int DEFAULT NULL,
          renew_post_id int DEFAULT NULL,
          content_type varchar(16) NOT NULL DEFAULT 'blog',
          kind enum('new','renew') NOT NULL DEFAULT 'new',
          job_status enum('pending','delivered','failed','permanent_failure') NOT NULL DEFAULT 'pending',
          error_message varchar(500) DEFAULT NULL,
          failed_at datetime DEFAULT NULL,
          retry_count tinyint unsigned NOT NULL DEFAULT 0,
          draft_title varchar(255) DEFAULT NULL,
          draft_category_id int DEFAULT NULL,
          draft_deleted_at datetime DEFAULT NULL,
          publish_status enum('pending','published','incomplete') NOT NULL DEFAULT 'pending',
          scheduled_at datetime DEFAULT NULL,
          last_checked_at datetime DEFAULT NULL,
          missing_reason varchar(500) DEFAULT NULL,
          renew_payload mediumtext DEFAULT NULL,
          requested_at datetime NOT NULL,
          delivered_at datetime DEFAULT NULL,
          published_at datetime DEFAULT NULL,
          PRIMARY KEY (id),
          UNIQUE KEY uq_factory_job_no (job_no),
          UNIQUE KEY uq_factory_draft (project_key, draft_id),
          UNIQUE KEY uq_factory_renew_post (project_key, content_type, renew_post_id),
          KEY idx_factory_due (job_status, publish_status, scheduled_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    }

    private function noticesDdl(string $table): string
    {
        return "CREATE TABLE `{$table}` (
          id int NOT NULL AUTO_INCREMENT,
          project_key varchar(50) NOT NULL,
          event_key varchar(32) NOT NULL,
          dedup_key varchar(120) NOT NULL,
          ok tinyint(1) NOT NULL DEFAULT 0,
          detail varchar(200) DEFAULT NULL,
          sent_at datetime NOT NULL,
          PRIMARY KEY (id),
          KEY idx_factory_notice (project_key, event_key, dedup_key, ok, sent_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    }
}
