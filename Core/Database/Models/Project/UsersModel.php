<?php

namespace Rbn\Framework\Core\Database\Models\Project;

use Rbn\Framework\Core\Base\Data\BaseModel;
use Rbn\Framework\Core\Support\Contracts\Base\BaseModelInterface;

/**
 * UsersModel - Proje Kullanıcı Katmanı 👥🛰️
 * 
 * @property int    $id
 * @property string $name
 * @property string $email
 * @property string $username
 * @property bool   $email_verified
 * @property int    $password_score
 * @property int    $is_active (1: Active, 0: Inactive, 2: Pending, 3: Banned)
 * @property string $role (developer, superadmin, admin, moderator, editor, user, guest)
 * @property bool   $is_online
 * @property string $last_activity (timestamp)
 * @property string $created_at
 * @property string $updated_at
 */
class UsersModel extends BaseModel implements BaseModelInterface
{
    protected string $connection = 'database_project';
    protected $table = 'z_users';
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

    /**
     * FW-BASE-2 (T4) — korumalı alan kara listesi 🛡️
     *
     * Bu üç alan hiçbir form arayüzünde kullanıcı tarafından normalde
     * gönderilmez; toplu atama (`create()`/`update()`/`save()`) ile yazılamaz.
     * Rol değişimi `UserManager::setRole()` yetkili yolundan geçer.
     *
     * KİLİT: `security.mass_assignment` bayrağı AÇIK olsa bile bu alanlar
     * yalnız `$fillable`/`$guarded` TANIMLAYAN modelde korunur; diğer 76 model
     * için davranış DEĞİŞMEZ.
     */
    protected array $guarded = ['role', 'email', 'username'];
}
