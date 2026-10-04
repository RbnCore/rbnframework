<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Models\Common;

use Rbn\Framework\Core\Base\Data\BaseModel;
use Rbn\Framework\Core\Support\Contracts\Base\BaseModelInterface;

/**
 * CmLogNotificationsModel - Shared Notification Logs 📨📜
 * 
 * RBN 3.5: Centralized logging for notifications in rbncore_common.
 * 
 * @property int         $id
 * @property string      $project_key
 * @property string      $type
 * @property string      $title
 * @property string      $message
 * @property string|null $url
 * @property string|null $metadata
 * @property int|null    $user_id
 * @property string      $priority
 * @property string|null $expires_at
 * @property int         $is_read
 * @property string|null $read_at
 * @property string      $created_at
 * @property string|null $updated_at
 */
class CmLogNotificationsModel extends BaseModel implements BaseModelInterface
{
    protected string $connection = 'database_common';
    protected $table = 'cm_log_notifications';
    protected bool $timestamps = false;
    protected bool $scoped = true;
}
