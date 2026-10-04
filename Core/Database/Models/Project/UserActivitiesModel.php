<?php

namespace Rbn\Framework\Core\Database\Models\Project;

use Rbn\Framework\Core\Base\Data\BaseModel;
use Rbn\Framework\Core\Support\Contracts\Base\BaseModelInterface;

/**
 * UserActivitiesModel - Kullanıcı aktivite loglarını yönetir.
 * Purified for RBN Framework v5.0 Masterpiece
 * 
 * @property int    $id
 * @property int    $user_id
 * @property string $email
 * @property string $activity_type (successful_login, failed_login, logout, error, etc)
 * @property string $ip_address
 * @property string $country_code
 * @property string $user_agent
 * @property string $details
 * @property string $created_at
 */
class UserActivitiesModel extends BaseModel implements BaseModelInterface
{
    protected string $connection = 'database_project';
    protected $table = 'z_users_activities';
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
