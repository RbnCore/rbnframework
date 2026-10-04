<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Models\Master;

use Rbn\Framework\Core\Base\Data\BaseModel;

/**
 * MasterSettingsModel - The Global Authority Configuration Model 🏛️🛰️⚓
 * 
 * RBN 3.5 Masterpiece: Sovereign orchestrator for the rbn_master settings table.
 * This is the Single Source of Truth (SSoT) for system-wide configurations.
 * 
 * @property int    $id             [PRIMARY]
 * @property string $type           [MECBUR - system|api|global]
 * @property string $category       [NULLABLE - api|auth|infra]
 * @property string $setting_name   [MECBUR - Human Readable]
 * @property string $setting_key    [MECBUR - UNIQUE]
 * @property string $setting_value  [NULLABLE - Dynamic Value]
 * @property string $description    [NULLABLE]
 * @property int    $is_active      [MECBUR - Default: 1]
 * @property string $created_at
 * @property string $updated_at
 */
class MasterSettingsModel extends BaseModel
{
    /**
     * Database Connection Identity 🪐
     * Forces this model to always use the central rbn_master authority.
     */
    protected string $connection = 'database_master';

    /**
     * Table Identity 🏺
     */
    protected $table = 'settings';
    /**
     * FW-ALTYAPI-2 H / G1 - kiraci izolasyonu BEYANI (beyan zorunlulugu).
     *
     * Kapsam  : master yonetim duzlemi.
     * Gerekce: master yonetim duzlemi: satir bir KIRACI degil, sistem kaydidir; kiraci bazli kapsam bu duzlemde uygulanmaz.
     *
     * Varsayilan `BaseModel::$scoped` DEGISTIRILMEDI: kiraci izolasyonu
     * varsayilan olarak KAPALI kalir (acmak girisi 7 veritabaninda
     * kirar - FW-ALTYAPI-1 H 4.2 olculdu). G1 yalniz BEYAN ZORUNLULUGU
     * getirir: her model kararini kendi dosyasinda yazar.
     *
     * @tenant-scope master
     */
    protected bool $scoped = false;

    /**
     * DNA Blueprint 🧬
     */
    protected bool $timestamps = true;
}
