<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Models\Project;

use Rbn\Framework\Core\Base\Data\BaseModel;
use Rbn\Framework\Core\Support\Contracts\Base\BaseModelInterface;

/**
 * SidebarMenusModel - Admin Panel Navigation Hub 🛠️🔗
 * 
 * @property int    $id
 * @property string $project_key
 * @property int    $category_id
 * @property int    $parent_id
 * @property string $menu_title
 * @property string $menu_slug
 * @property string $menu_url
 * @property string $menu_icon
 * @property int    $order_num
 * @property int    $is_active
 * @property string $required_role
 * @property string $created_at
 * @property string $updated_at
 */
class SidebarMenusModel extends BaseModel implements BaseModelInterface
{
    protected string $connection = 'database_project';
    protected $table = 'z_bs_sidebar_menus';
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
