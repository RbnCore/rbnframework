<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Models\Common;

use Rbn\Framework\Core\Base\Data\BaseModel;
use Rbn\Framework\Core\Support\Contracts\Base\BaseModelInterface;

/**
 * CmSysIpBlocksModel - Global & Project IP Blacklist Hub 🚧🛡️
 * 
 * RBN 3.5: Shared operational layer for managing blocked IP addresses in rbncore_common.
 * 
 * @property int         $id
 * @property string      $project_key  // 'GLOBAL' or project specific key
 * @property string      $ip_address
 * @property string|null $country_code
 * @property string      $reason
 * @property string      $blocked_until
 * @property string      $created_at
 * @property string|null $updated_at
 */
class CmSysIpBlocksModel extends BaseModel implements BaseModelInterface
{
    protected string $connection = 'database_common';
    protected $table = 'cm_sys_ip_blocks';
    protected bool $timestamps = false;
    protected bool $scoped = true;

    /**
     * [FW-ALTYAPI-3 / H · G4] Kiraci kapsamı istisnasi 🔓
     *
     * Bu tablo iki tür kaydı taşır: proje özel IP blokları **ve** tüm
     * projeler için geçerli `GLOBAL` kuralları. Önceden istisna
     * `QueryModelTrait` içine **tablo adı sabiti** olarak gömülüydü; kural
     * kabukta kaldığı için ikinci bir tablo daha eklenemezdi.
     *
     * ÖLÇÜLEN GERÇEK (yerel `rbncore_common`): `cm_sys_ip_blocks` dağıtımı
     * `GLOBAL` + proje anahtarları → istisna kaybı = güvenlik kaybı.
     */
    protected array $projectScopeIncludes = ['GLOBAL'];
}
