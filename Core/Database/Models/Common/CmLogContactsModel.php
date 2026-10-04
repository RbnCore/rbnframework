<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Models\Common;

use Rbn\Framework\Core\Base\Data\BaseModel;
use Rbn\Framework\Core\Support\Contracts\Base\BaseModelInterface;

/**
 * CmLogContactsModel - Centralized Contact / Lead Form Hub 📬✨
 * 
 * RBN 3.5: Centralized storage for contact messages across projects in rbncore_common.
 * 
 * @property int         $id
 * @property string      $project_key
 * @property string      $name
 * @property string      $email
 * @property string|null $subject
 * @property string      $message
 * @property int         $privacy_accepted
 * @property int         $is_read
 * @property string|null $read_at
 * @property string      $created_at
 * @property string|null $updated_at
 */
class CmLogContactsModel extends BaseModel implements BaseModelInterface
{
    protected string $connection = 'database_common';
    protected $table = 'cm_log_contacts';
    protected bool $timestamps = false;
    protected bool $scoped = true;
}
