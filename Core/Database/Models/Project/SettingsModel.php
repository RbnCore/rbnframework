<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Models\Project;

use Rbn\Framework\Core\Base\Data\BaseModel;

/**
 * SettingsModel - Framework Central Settings Hub ⚙️🧬
 * 
 * RBN Framework: Modular service component for project-level configurations.
 * 
 * @property int    $id             [PRIMARY]
 * @property int    $group_id       [MECBUR - FOREIGN]
 * @property string $setting_key    [MECBUR - UNIQUE]
 * @property string $setting_value  [NULLABLE - Dynamic Value]
 * @property string $label_tr       [MECBUR - Localized Name]
 * @property string $label_en       [NULLABLE]
 * @property string $field_type     [Default: text]
 * @property string $field_options  [NULLABLE - JSON]
 * @property string $help_text_tr   [NULLABLE]
 * @property string $help_text_en   [NULLABLE]
 * @property string $required_role  [MECBUR - admin|developer|user]
 * @property int    $is_active      [MECBUR - Default: 1]
 * @property int    $order_num      [Default: 0]
 * @property bool   $is_required    [VIRTUAL/DYNAMIK - Framework Support]
 * @property string $created_at
 * @property string $updated_at
 */
class SettingsModel extends BaseModel
{
    protected string $connection = 'database_project';
    protected $table = 'z_settings';
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
