<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Models\Project;

use Rbn\Framework\Core\Base\Data\BaseModel;
use Rbn\Framework\Core\Support\Contracts\Base\BaseModelInterface;

/**
 * FrontendMenusModel - UI Navigation Hub 🌐🔗
 * 
 * @property int    $id
 * @property string $title
 * @property string $project_key
 * @property string $url
 * @property int    $parent_id
 * @property int    $order_num
 * @property int    $is_active
 * @property string $target
 * @property string $icon
 * @property string $created_at
 * @property string $updated_at
 */
class FrontendMenusModel extends BaseModel implements BaseModelInterface
{
    protected string $connection = 'database_project';
    protected $table = 'z_app_menus_frontend';
    /**
     * FW-ALTYAPI-2 H / G1 - kiraci izolasyonu BEYANI (beyan zorunlulugu).
     *
     * Kapsam  : kolonu var, tablo gercekten cok-kiracili.
     * Gerekce: kiraci kolonu ZATEN var ve tablo olcumde gercekten cok-kiracili; kapsam BU DALGADA acilmaz, G4 dongusunde `true`ye cevrilecek.
     *
     * Varsayilan `BaseModel::$scoped` DEGISTIRILMEDI: kiraci izolasyonu
     * varsayilan olarak KAPALI kalir (acmak girisi 7 veritabaninda
     * kirar - FW-ALTYAPI-1 H 4.2 olculdu). G1 yalniz BEYAN ZORUNLULUGU
     * getirir: her model kararini kendi dosyasinda yazar.
     *
     * @tenant-scope multi-tenant-ready
     */
    protected bool $scoped = true;
    protected bool $timestamps = true;
}
