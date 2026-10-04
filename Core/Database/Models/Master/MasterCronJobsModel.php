<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Models\Master;

use Rbn\Framework\Core\Base\Data\BaseModel;
use Rbn\Framework\Core\Support\Contracts\Base\BaseModelInterface;

/**
 * MasterCronJobsModel - Global Task Automation Hub 🕒⚙️
 * 
 * @property int    $id
 * @property string $project_key
 * @property string $name
 * @property string $task_key
 * @property string $task_class?
 * @property int    $frequency
 * @property string $last_run_at
 * @property string $next_run_at
 * @property int    $is_active
 * @property string $params
 * @property string $created_at
 * @property string $updated_at
 */
class MasterCronJobsModel extends BaseModel implements BaseModelInterface
{
    protected string $connection = 'database_master';
    protected $table = 'cron_jobs';
    /**
     * FW-ALTYAPI-2 H / G1 - kiraci izolasyonu BEYANI (beyan zorunlulugu).
     *
     * Kapsam  : kiraci KAYDI tablosu.
     * Gerekce: tablonun kendisi kiraci KAYDIDIR: kolon satyrin konusudur, aktif kiraci degil; kapsam buraya uygulanamaz.
     *
     * Varsayilan `BaseModel::$scoped` DEGISTIRILMEDI: kiraci izolasyonu
     * varsayilan olarak KAPALI kalir (acmak girisi 7 veritabaninda
     * kirar - FW-ALTYAPI-1 H 4.2 olculdu). G1 yalniz BEYAN ZORUNLULUGU
     * getirir: her model kararini kendi dosyasinda yazar.
     *
     * @tenant-scope tenant-record
     */
    protected bool $scoped = false;
    protected bool $timestamps = true;
}
