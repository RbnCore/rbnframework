<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Models\Master;

use Rbn\Framework\Core\Base\Data\BaseModel;
use Rbn\Framework\Core\Support\Contracts\Base\BaseModelInterface;

/**
 * MasterIpWhitelistModel - Whitelist Orchestration Hub 🛡️🕊️
 * 
 * RBN 3.5: Lean data layer for managing trusted IP addresses.
 * Powered by ActionModelTrait for advanced querying.
 * 
 * @property int    $id
 * @property string $ip_address
 * @property string $country_code
 * @property string $label
 * @property string $created_at
 */
class MasterIpWhitelistModel extends BaseModel implements BaseModelInterface
{
    protected string $connection = 'database_master';
    protected $table = 'ip_whitelist';
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
}
