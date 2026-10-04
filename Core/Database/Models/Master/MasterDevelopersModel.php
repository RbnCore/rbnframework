<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Models\Master;

use Rbn\Framework\Core\Base\Data\BaseModel;
use Rbn\Framework\Core\Support\Contracts\Base\BaseModelInterface;

/**
 * MasterDevelopersModel - The Identity Authority Hub 🕵️‍♂️🛰️
 * 
 * RBN 3.5: Targeted model for developer/VIP validation on the 'master' DB.
 * 
 * @property int    $id
 * @property string $username
 * @property string $password
 * @property string $email
 * @property string $dev_token_hash
 * @property string $role (developer, superadmin)
 * @property string $full_name
 * @property string $ip_whitelist
 * @property string $created_at
 * @property string $updated_at
 */
class MasterDevelopersModel extends BaseModel implements BaseModelInterface
{
    protected string $connection = 'database_master';
    protected $table = 'developers';
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
    protected bool $timestamps = true;

    /**
     * FW-BASE-2 (T4) — korumalı alan kara listesi 🛡️
     *
     * `role` yetki alanı, `password` ise sunucuda üretilen özet (hash)
     * alanıdır; ikisi de toplu atama ile yazılamaz. Parola değişimi
     * `UserRepository::updatePassword()` (hash burada üretilir) yolundan,
     * rol değişimi `UserManager::setRole()` yolundan geçer.
     */
    protected array $guarded = ['role', 'password'];
}
