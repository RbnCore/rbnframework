<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Syshub\Providers;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * SyshubCronProvider - Syshub Cron Veri Sağlayıcısı ⚙️🛰️⚓
 * RBN Framework Standard.
 * @property \Rbn\Framework\Core\Database\Models\Master\MasterCronJobsModel $masterCronJobModel
 * @property \Rbn\Framework\Core\Database\Models\Project\CronLogsModel $cronLogModel
 */
class SyshubCronProvider extends BaseComponent
{
    /** @var string Otonom Model Mührü ⚓ */
    protected $targetModel = 'cronJobs';

    /**
     * Tüm görevleri çek 📋
     */
    public function getJobs(): array
    {
        return $this->model('master.cronJob')?->orderBy('id', 'DESC')->get()->toArray() ?? [];
    }

    /**
     * Crontab komutunu otonom olarak üret 🕰️
     */
    public function getCrontabCommand(): string
    {
        return $this->service('cron')->getCrontabCommand();
    }

    /**
     * Logları çek 📜
     */
    public function getLogs(int $limit = 100): array
    {
        return $this->model('project.cronLog')?->orderBy('id', 'DESC')->limit($limit)->get()->toArray() ?? [];
    }

    /**
     * Sistemdeki temizlenebilir klasörleri çek 📁⚓
     */
    public function getStorageFolders(): array
    {
        $storagePath = \Rbn\Framework\Core\System\Paths\Paths::project()->storage();
        $storageFolders = [];

        if (is_dir($storagePath)) {
            $items = scandir($storagePath);
            foreach ($items as $item) {
                if ($item !== '.' && $item !== '..' && is_dir($storagePath . DIRECTORY_SEPARATOR . $item)) {
                    $storageFolders[] = $item;
                }
            }
        }

        return $storageFolders;
    }

    /**
     * Tekil görev çek 🎯
     */
    public function find(int $id): array
    {
        return $this->model('cronJobs')->find($id)?->toArray() ?? [];
    }
}
