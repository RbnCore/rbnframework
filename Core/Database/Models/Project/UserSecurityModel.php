<?php

namespace Rbn\Framework\Core\Database\Models\Project;

use Rbn\Framework\Core\Base\Data\BaseModel;
use Rbn\Framework\Core\Support\Contracts\Base\BaseModelInterface;

/**
 * UserSecurityModel - Güvenlik Anahtar ve Sır Yönetim Katmanı 🔐🛡️
 * 
 * Table: user_security
 * 
 * @property int    $user_id           (Primary Key, FK → users.id)
 * @property string $password_hash     Bcrypt password hash
 * @property string $email_hash        Hashed email for lookup
 * @property string $phone_hash        Hashed phone for lookup
 * @property string $remember_token    Hashed remember-me cookie token
 * @property string $verification_token Email verification token
 * @property string $reset_token       Password reset token
 * @property string $last_login_at     Last successful login timestamp
 * @property string $last_ip           Last known IP address
 * @property string $updated_at        Last record update
 */
class UserSecurityModel extends BaseModel implements BaseModelInterface
{
    protected string $connection = 'database_project';
    protected $table = 'z_users_security';
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
    protected $primaryKey = 'user_id';

    /**
     * FW-BASE-2 (T4) — korumalı alan kara listesi 🛡️
     *
     * `user_id` bu tablonun birincil anahtarıdır ve DAIMA metot imzasından
     * gelmelidir (`UserSecurityRepository::saveSecurityData($userId, ...)`).
     * Ham veriden gelen `user_id` ile başka bir kullanıcının güvenlik kasasına
     * parola yazılamaz.
     */
    protected array $guarded = ['user_id'];
}
