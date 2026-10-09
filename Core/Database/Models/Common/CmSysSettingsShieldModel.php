<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Models\Common;

use Rbn\Framework\Core\Base\Data\BaseModel;
use Rbn\Framework\Core\Support\Contracts\Base\BaseModelInterface;

/**
 * CmSysSettingsShieldModel - Centralized Security & Firewall Configuration Hub 🛡️⚙️
 * 
 * RBN Framework: Shared operational layer for managing firewall and shield settings in rbncore_common.
 * 
 * @property int         $id
 * @property string      $project_key
 * @property string      $group_key
 * @property string      $setting_key
 * @property string      $setting_value
 * @property string      $value_type (string, integer, boolean, json)
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class CmSysSettingsShieldModel extends BaseModel implements BaseModelInterface
{
    protected string $connection = 'database_common';
    protected $table = 'cm_sys_settings_shield';
    protected bool $timestamps = true;
    protected bool $scoped = true; // 🛡️ Multi-Tenant Scoping
}
