<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Models\Project;

use Rbn\Framework\Core\Base\Data\BaseModel;

/**
 * SettingsGroupModel - Framework Central Setting Groups 📂🧬
 * 
 * RBN Framework: Modular service component for organizing project settings.
 * 
 * @property int    $id
 * @property string $group_key
 * @property string $group_label
 * @property string $group_description
 * @property string $group_icon
 * @property int    $order_num
 * @property int    $is_active
 * @property string $created_at
 * @property string $updated_at
 */
class SettingsGroupModel extends BaseModel
{
    protected string $connection = 'database_project';
    protected $table = 'z_setting_groups';
    /**
     * FW-ALTYAPI-2 H / G1 - kiraci izolasyonu BEYANI (beyan zorunlulugu).
     *
     * Kapsam  : kimlik/oturum tablosu.
     * Gerekce: kimlik tablosu: `project_key` kapsam alani degil, ayri veritabani zaten kiraci ayrimi yapar; kolon bilincli EKLENMEZ.
     *
     * Varsayilan `BaseModel::$scoped` DEGISTIRILMEDI: kiraci izolasyonu
     * varsayilan olarak KAPALI kalir (acmak girisi 7 veritabaninda
     * kirar - FW-ALTYAPI-1 H 4.2 olculdu). G1 yalniz BEYAN ZORUNLULUGU
     * getirir: her model kararini kendi dosyasinda yazar.
     *
     * @tenant-scope identity
     */
    protected bool $scoped = false;
    protected bool $timestamps = true;
}
