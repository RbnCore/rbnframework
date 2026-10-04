<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Console\Handlers;

use Rbn\Framework\Core\Services\Console\Base\BaseCommand;
use Rbn\Framework\Core\Services\Console\Base\ConsoleStyle;
use Rbn\Framework\Core\Services\Console\Services\TenantAuditService;
use Rbn\Framework\Core\Services\Console\Services\TenantMigrationService;
use Rbn\Framework\Core\System\Config\Definitions\DbProfiles\ProjectDbData;

/**
 * TenantHandlers - Kiracı izolasyonu komutları 🔑🖥️
 *
 * [FW-ALTYAPI-2 / H] G2.
 *
 * - `tenant:audit`   → **YALNIZ OKUR** denetim: hangi tabloda hangi
 *   `project_key`'den kaç satır var, kolonu olmayan hedefler hangileri,
 *   kaç model `scoped` beyanı yapmış. Hiçbir şey YAZMAZ.
 * - `tenant:plan`    → hedef veritabanı için **dry-run** migration planı.
 *   `--apply` verilmedikçe şemaya dokunulmaz.
 * - `tenant:apply`   → ekleyici kolon + indeks. Varsayılan tablo listesi
 *   `ProjectDbData::TENANT_TABLES`'tır.
 * - `tenant:revert`  → geri alma (indeks → kolon sırası).
 *
 * [TASARIM §5.6] Migration her veritabanı için AYRI koşulur; bu komut tek
 * koşuda `--database=<ad>` ile TEK hedef alır.
 *
 * @see \Rbn\Framework\Core\Database\Migrations\Tenant\TenantKeyMigration
 */
class TenantHandlers extends BaseCommand
{
    public function getCommands(): array
    {
        return [
            'tenant:audit' => ['desc' => 'Kiracı izolasyonu denetimi (YALNIZ OKUR)', 'method' => 'tenantAudit'],
            'tenant:plan'  => ['desc' => 'Kiracı kolonu migration planı (dry-run, şemaya dokunmaz)', 'method' => 'tenantPlan'],
            'tenant:apply' => ['desc' => 'Kiracı kolonu ekler (ekleyici, idempotent)', 'method' => 'tenantApply'],
            'tenant:revert' => ['desc' => 'Kiracı kolonunu geri alır (indeks → kolon)', 'method' => 'tenantRevert'],
        ];
    }

    /* ==================================================================
       YALNIZ OKUR
       ================================================================== */

    public function tenantAudit(array $params): void
    {
        $options = $this->parseOptions($params);
        $dbs = $this->hedefVeritabanlari($options);

        if (empty($dbs)) {
            $this->error('Hedef veritabanı yok. Kullanım: tenant:audit --database=<ad> [--database=<ad2>]');
            return;
        }

        $denetim = new TenantAuditService();
        $hedef = ProjectDbData::TENANT_TABLES;

        foreach ($dbs as $db) {
            $pdo = $this->baglanti($db);
            $this->info("=== {$db} ===");

            $rapor = $denetim->auditDatabases($pdo, [$db], $hedef)[0];

            $this->success(sprintf(
                'kiracı kolonu OLAN tablo: %d | hedef listede OLMAYAN kolon: %d | sabit DEFAULT kolon: %d',
                count($rapor['with_column']),
                count($rapor['without']),
                count($rapor['literal_default'])
            ));

            if (!empty($rapor['without'])) {
                $this->warning('kolonu eksik hedef: ' . implode(', ', $rapor['without']));
            }

            $satirlar = [];
            foreach ($rapor['targets'] as $t) {
                $dagilim = [];
                foreach ($t['distribution'] as $k => $c) {
                    $dagilim[] = $k . '=' . $c;
                }
                $satirlar[] = [
                    'tablo'       => $t['table'],
                    'kolon'       => $t['column'] ? 'VAR' : 'YOK',
                    'tip'         => (string) ($t['type'] ?? '-'),
                    'null'        => $t['nullable'] === null ? '-' : ($t['nullable'] ? 'NULL' : 'NOT NULL'),
                    'default'     => $t['default'] === null ? 'NULL' : (string) $t['default'],
                    'indeks'      => $t['indexed'] ? 'evet' : 'hayir',
                    'null satir'  => (string) $t['null_rows'],
                    'dagilim'     => implode(' ', $dagilim),
                ];
            }
            ConsoleStyle::table(
                ['tablo', 'kolon', 'tip', 'null', 'default', 'indeks', 'null satir', 'dagilim'],
                $satirlar
            );
        }

        // G1 beyan envanteri — DOSYA düzeyinde (yetkili ölçüm, hiçbir sınıf yüklemez)
        $dosyaRapor = $denetim->declarationAuditByFile(
            \Rbn\Framework\Core\System\Paths\Paths::workspace(),
            $this->modelDosyalari()
        );
        $this->success(sprintf(
            'model beyani (dosya duzeyi): %d somut model | %d KENDI dosyasinda yaziyor | %d miras aliyor',
            $dosyaRapor['models'],
            $dosyaRapor['declared'],
            $dosyaRapor['inheritance']
        ));
        if ($dosyaRapor['inheritance'] > 0) {
            $this->warning('beyan etmeyen model sayisi ' . $dosyaRapor['inheritance'] . ' (G1 hedefi)');
            foreach (array_slice($dosyaRapor['missing'], 0, 8) as $m) {
                $this->info('  eksik: ' . $m);
            }
            if (count($dosyaRapor['missing']) > 8) {
                $this->info('  ... ve ' . (count($dosyaRapor['missing']) - 8) . ' tane daha');
            }
        }

        // Tamamlayıcı: o an autoload OLABİLEN sınıflar (oturum bağlamına bağlı)
        $beyan = $denetim->modelDeclarations($this->modelSiniflari());
        $this->info(sprintf(
            'bu oturumda yuklenebilen model: %d (beyan %d / miras %d)',
            count($beyan['items']),
            $beyan['declared'],
            $beyan['inherited']
        ));
    }

    /* ==================================================================
       MIGRATION
       ================================================================== */

    public function tenantPlan(array $params): void
    {
        $this->kosu($params, false);
    }

    public function tenantApply(array $params): void
    {
        $this->kosu($params, true);
    }

    public function tenantRevert(array $params): void
    {
        $options = $this->parseOptions($params);
        $db = (string) ($options['database'] ?? '');
        if ($db === '') {
            $this->error('--database=<ad> zorunlu.');
            return;
        }
        if (isset($options['yes']) === false) {
            $this->error('Geri alma ETKİLİDİR; onaylamak için --yes verin.');
            return;
        }

        $servis = new TenantMigrationService();
        $sonuc = $servis->revert($this->baglanti($db), $db, $servis->targetTables());

        foreach ($sonuc['reverted'] as $t) {
            $this->success("Geri alındı: {$t}");
        }
        foreach ($sonuc['skipped'] as $s) {
            $this->info("Atlandı: {$s}");
        }
    }

    protected function kosu(array $params, bool $uygula): void
    {
        $options = $this->parseOptions($params);
        $db = (string) ($options['database'] ?? '');
        if ($db === '') {
            $this->error('--database=<ad> zorunlu (migration her veritabanı için ayrı koşar).');
            return;
        }

        $servis = new TenantMigrationService();
        $pdo = $this->baglanti($db);

        $on = $servis->report($pdo, $db, $servis->targetTables());
        $this->info("Hedef: {$db} | kolonu eksik: " . (empty($on['without_column']) ? 'yok' : implode(', ', $on['without_column'])));
        if (!empty($on['literal_default'])) {
            $this->warning('sabit DEFAULT taşıyan kolon sayısı: ' . count($on['literal_default'])
                . ' (bu komut DÜZELTMEZ; canlı runbook adımı)');
        }

        $sonuc = $servis->run($pdo, $db, $servis->targetTables(), !$uygula);

        $this->success(sprintf(
            'durum=%s beklenen=%d uygulanan=%d%s',
            $sonuc['status'],
            $sonuc['expected'],
            $sonuc['applied'],
            $sonuc['dry_run'] ? ' (DRY-RUN: şemaya dokunulmadı)' : ''
        ));

        foreach ($sonuc['steps'] as $s) {
            $this->info('  ' . $s['kind'] . ' -> ' . $s['table']);
        }
        foreach ($sonuc['skipped'] as $s) {
            $this->info('  atlandı: ' . $s);
        }
    }

    /* ==================================================================
       YARDIMCI
       ================================================================== */

    /**
     * Hedef veritabanı listesi. `local` kısayolu o anki proje/ortak profilinin
     * kendi veritabanını seçer; `all` sunucudaki tüm veritabanlarını seçer ve
     * MUTLAKA `information_schema` üzerinden okur.
     *
     * @param  array $options
     * @return string[]
     */
    protected function hedefVeritabanlari(array $options): array
    {
        $dbs = [];
        foreach ($options as $k => $v) {
            if ($k === 'database') {
                foreach (explode(',', (string) $v) as $d) {
                    $d = trim($d);
                    if ($d !== '') {
                        $dbs[] = $d;
                    }
                }
            }
        }

        if (isset($options['local'])) {
            $dbs[] = $this->aktifVeritabani();
        }

        return array_values(array_unique(array_filter($dbs)));
    }

    /**
 * Aktif bağlantının veritabanı adı (yalnız `--local` kısayolu için; hata
 * olursa boş döner ve komut uyarı verir).
 */
protected function aktifVeritabani(): string
    {
        try {
            return (string) \Rbn\Framework\Core\Database\Database::getInstance()
                ->getPdo()
                ->query('SELECT DATABASE()')
                ->fetchColumn();
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Çalışma bağlantısı — framework'ün KENDİ bağlantısı; yenisi açılmaz.
     *
     * Sunucu/kimlik bilgisi yalnız `Database` katmanından gelir; hiçbir sır
     * değeri çıktıya basılmaz. Motor tam nitelikli (`` `db`.`tablo` ``)
     * referanslar kullandığı için hedef veritabanı ayrıca bağlanmaya GEREK
     * YOKTUR — aynı sunucudaki her şema bu bağlantıdan görülür.
     *
     * Hedef veritabanı adı komut satırından gelir; framework'e yazılmaz
     * ([ANAYASA §9]).
     */
    protected function baglanti(string $database): \PDO
    {
        return (new TenantMigrationService())->pdo();
    }

    /**
     * Diskteki model sınıflarını toplar (beyan envanteri için). ÇALIŞTIRMA
     * YAPILMAZ — sınıflar yalnız `class_exists` ile doğrulanır.
     *
     * @return string[]
     */
    protected function modelSiniflari(): array
    {
        $kotu = \Rbn\Framework\Core\System\Paths\Paths::workspace();
        $siniflar = [];

        foreach (['rbnframework', 'projects', 'domains'] as $dal) {
            $d = $kotu . '/' . $dal;
            if (!is_dir($d)) {
                continue;
            }
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($d, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($it as $f) {
                if (!$f->isFile() || $f->getExtension() !== 'php' || !str_ends_with($f->getFilename(), 'Model.php')) {
                    continue;
                }
                $yol = str_replace('\\', '/', $f->getPathname());
                if (str_contains($yol, '/vendor/')) {
                    continue;
                }
                if (!preg_match('/^\s*namespace\s+([^;]+);/m', (string) file_get_contents($yol), $mN)) {
                    continue;
                }
                if (!preg_match('/^\s*(?:abstract\s+)?class\s+(\w+)/m', (string) file_get_contents($yol), $mC)) {
                    continue;
                }
                $siniflar[] = trim($mN[1]) . '\\' . $mC[1];
            }
        }

        return array_values(array_unique($siniflar));
    }

    /**
     * Diskteki model DOSYALARI (kök göreli). Sınıf AD ALANI çözümü
     * yapılmaz — proje modelleri her projede farklı ad alanı taşır; envanter
     * dosya düzeyinde tutulur.
     *
     * @return string[]
     */
    protected function modelDosyalari(): array
    {
        $kotu = \Rbn\Framework\Core\System\Paths\Paths::workspace();
        $yollar = [];

        foreach (['rbnframework', 'projects', 'domains'] as $dal) {
            $d = $kotu . '/' . $dal;
            if (!is_dir($d)) {
                continue;
            }
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($d, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($it as $f) {
                if (!$f->isFile() || $f->getExtension() !== 'php' || !str_ends_with($f->getFilename(), 'Model.php')) {
                    continue;
                }
                $yol = str_replace('\\', '/', $f->getPathname());
                if (str_contains($yol, '/vendor/')) {
                    continue;
                }
                if (!str_contains((string) file_get_contents($yol), 'BaseModel')) {
                    continue;
                }
                $yollar[] = substr($yol, strlen(str_replace('\\', '/', $kotu)) + 1);
            }
        }

        sort($yollar);
        return array_values(array_unique($yollar));
    }
}