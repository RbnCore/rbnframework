<?php

namespace Rbn\Framework\Core\Services\Console\Handlers;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\System\Config\Definitions\DbProfiles\MasterDbData;
use Rbn\Framework\Core\System\Config\Engine\Database\DbProfileResolver;
use Rbn\Framework\Core\Services\Console\Base\ConsoleStyle;
use Rbn\Framework\Core\Services\Console\Base\BaseCommand;

class SystemHandlers extends BaseCommand
{
    public function getCommands(): array
    {
        return [
            'list' => ['desc' => 'Tüm müsait komutları listeler', 'method' => 'listCommands'],
            'system:doctor' => ['desc' => 'Sistem sağlığını, Master DB ve tüm projeleri röntgenleyip teşhis eder 🩺', 'method' => 'systemDoctor'],
            'doctor' => ['desc' => 'Sistem sağlığını teşhis eder (system:doctor kısayolu) 🩺', 'method' => 'systemDoctor'],
            'system:cron' => ['desc' => 'Zamanlanmış görevleri (cron) çalıştırır', 'method' => 'runCron'],
            'project:list' => ['desc' => 'Sistemdeki tüm projeleri listeler', 'method' => 'projectList'],
            'cache:clear' => ['desc' => 'Sistem ve proje önbelleğini (.cache) ve geçici dosyaları temizler', 'method' => 'cacheClear'],
            'logs:clear'  => ['desc' => 'Sistem ve proje log dosyalarını temizler', 'method' => 'logsClear'],
            'tmp:clear'   => ['desc' => 'E:\\localhost\\tmp dizinindeki geçici scratch/test dosyalarını temizler', 'method' => 'tmpClear'],
        ];
    }

    public function systemDoctor(array $params): void
    {
        ConsoleStyle::header("🩺 RBN Framework Teşhis Servisi (System Doctor)");

        // 1. Master Database Connection Test
        try {
            $db = \Rbn\Framework\Core\Database\Database::getInstance();
        $masterDb = DbProfileResolver::databaseName(MasterDbData::class);
            $db->connection('database_master');
            $db->raw("SELECT 1");
            $this->success(str_pad("Master DB Bağlantısı ({$masterDb})", 45) . " [OK]");
        } catch (\Throwable $e) {
            $this->error(str_pad("Master DB Bağlantısı", 45) . " [FAIL: " . $e->getMessage() . "]");
        }

        // 2. Active Projects Integrity & DB Test
        try {
        $masterDb = DbProfileResolver::databaseName(MasterDbData::class);
            $db->connection('database_master');
            $projects = $db->raw("SELECT id, project_key, project_name FROM `{$masterDb}`.`projects` WHERE status = 'active'");

            ConsoleStyle::info("\n Proje Veritabanı ve Bağlam Teşhisi:");
            foreach ($projects as $proj) {
                $pKey = $proj['project_key'];
                $pName = str_pad("{$proj['project_name']} ({$pKey})", 45);

                try {
                    $this->switchProjectContext($pKey);
                    $db->connection('database_project');
                    $db->raw("SELECT 1");
                    $this->success("  ✔ " . $pName . " [OK]");
                } catch (\Throwable $pErr) {
                    $this->error("  ✘ " . $pName . " [FAIL: " . $pErr->getMessage() . "]");
                }
            }
            $db->connection('database_master');
        } catch (\Throwable $e) {
            $this->error("Proje teşhis hatası: " . $e->getMessage());
        }

        // 3. System Paths & Cache Permissions
        ConsoleStyle::info("\n Dizin ve İzin Kontrolleri:");
        $cacheDir = \Rbn\Framework\Core\System\Paths\Paths::workspace() . DIRECTORY_SEPARATOR . '.cache';
        $tmpDir = \Rbn\Framework\Core\System\Paths\Paths::workspace() . DIRECTORY_SEPARATOR . 'tmp';

        $this->info(str_pad("Önbellek Dizini (.cache)", 45) . (is_writable($cacheDir) ? " [OK - Yazılabilir]" : " [FAIL - İzin Yok]"));
        $this->info(str_pad("Geçici Scratch Dizini (tmp/)", 45) . (is_writable($tmpDir) ? " [OK - Yazılabilir]" : " [FAIL - İzin Yok]"));
        echo "\n";
    }

    public function projectList(array $params): void
    {
        try {
            $db = \Rbn\Framework\Core\Database\Database::getInstance();
            $db->connection('database_master');
        $masterDb = DbProfileResolver::databaseName(MasterDbData::class);
            $projects = $db->raw("SELECT id, project_key, project_name, domain, status FROM `{$masterDb}`.`projects`");

            if (empty($projects)) {
                $this->error("Kayıtlı proje bulunamadı.");
                return;
            }

            ConsoleStyle::table(['id', 'project_key', 'project_name', 'domain', 'status'], $projects);
        } catch (\Throwable $e) {
            $this->error("Proje listesi alınamadı: " . $e->getMessage());
        }
    }

    public function cacheClear(array $params): void
    {
        $options = $this->parseOptions($params);
        $projectKey = $options['project'] ?? ($options['project_key'] ?? null);

        $cacheDir = \Rbn\Framework\Core\System\Paths\Paths::workspace() . DIRECTORY_SEPARATOR . '.cache';
        $count = 0;

        if (is_dir($cacheDir)) {
            $pattern = $projectKey ? $cacheDir . DIRECTORY_SEPARATOR . "project_{$projectKey}.json" : $cacheDir . DIRECTORY_SEPARATOR . '*.json';
            $files = glob($pattern);
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                    $count++;
                }
            }
        }

        // Keşif haritaları (`components_map_<site>.json`, `discovery_map_<site>.php`) da silinir:
        // dosya varken disk taranmaz, yeni `#[Component]` sınıfı görünmez. Bir proje klasörü
        // birden çok siteye hizmet ettiği için klasördeki TÜM site haritaları gider; ilk istek
        // (web ya da proje bağlamına geçmiş CLI) yeniden üretir.
        $mapCount = 0;
        $projectsRoot = \Rbn\Framework\Core\System\Paths\Paths::workspace() . DIRECTORY_SEPARATOR . 'projects';
        $mapDirs = $projectKey
            ? [\Rbn\Framework\Core\System\Paths\Paths::project()->root() . DIRECTORY_SEPARATOR . 'Storage' . DIRECTORY_SEPARATOR . 'framework']
            : (glob($projectsRoot . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'Storage' . DIRECTORY_SEPARATOR . 'framework', GLOB_ONLYDIR) ?: []);
        foreach ($mapDirs as $mapDir) {
            foreach (array_merge(glob($mapDir . DIRECTORY_SEPARATOR . 'components_map_*.json') ?: [], glob($mapDir . DIRECTORY_SEPARATOR . 'discovery_map_*.php') ?: []) as $file) {
                if (is_file($file) && unlink($file)) {
                    $mapCount++;
                }
            }
        }

        $targetText = $projectKey ? "[{$projectKey}] projesinin" : "Tüm sistem ve projelerin";
        $this->success("{$targetText} önbelleği temizlendi! Toplam {$count} cache dosyası ve {$mapCount} keşif haritası silindi.");
        
        // Opsiyonel olarak tmp klasörünü de süpür
        if (!empty($options['with-tmp']) || !empty($options['all'])) {
            $this->tmpClear($params);
        }
    }

    public function tmpClear(array $params): void
    {
        $tmpDir = \Rbn\Framework\Core\System\Paths\Paths::workspace() . DIRECTORY_SEPARATOR . 'tmp';
        if (is_dir($tmpDir)) {
            $count = 0;
            $items = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($tmpDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($items as $item) {
                if ($item->isDir()) {
                    @rmdir($item->getRealPath());
                } else {
                    @unlink($item->getRealPath());
                    $count++;
                }
            }
            $this->success("Geçici (tmp/) klasörü ve alt dizinleri temizlendi! (Toplam {$count} dosya/klasör silindi)");
        } else {
            $this->info("Temizlenecek tmp/ dizini bulunamadı.");
        }
    }

    public function logsClear(array $params): void
    {
        $options = $this->parseOptions($params);
        $projectKey = $options['project'] ?? ($options['project_key'] ?? null);

        $logsDir = \Rbn\Framework\Core\System\Paths\Paths::workspace() . DIRECTORY_SEPARATOR . 'logs';
        $count = 0;

        if (is_dir($logsDir)) {
            $pattern = $projectKey ? $logsDir . DIRECTORY_SEPARATOR . "*{$projectKey}*.log" : $logsDir . DIRECTORY_SEPARATOR . '*.log';
            $files = glob($pattern);
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                    $count++;
                }
            }
        }

        $targetText = $projectKey ? "[{$projectKey}] projesinin" : "Tüm sistem";
        $this->success("{$targetText} log dosyaları temizlendi! Toplam {$count} .log dosyası silindi.");
    }

    public function runCron(array $params): void
    {
        @set_time_limit(120); // 2 dakika maksimum çalışma süresi (Sonsuz döngü ve kilitlenme koruması)

        ConsoleStyle::info("Zamanlanmış görevler (Cron) başlatılıyor...");
        $cronService = BaseService::get()->service('cron');
        if (!$cronService) {
            $this->error("Cron servisi bulunamadı.");
            return;
        }

        $options = $this->parseOptions($params);
        $cronService->runMaster($options);
        $this->success("Görevler kontrol edildi ve akış tamamlandı.");
    }
}
