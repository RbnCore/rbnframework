<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Models\Common;

use Rbn\Framework\Core\Base\Data\BaseModel;
use Rbn\Framework\Core\Support\Contracts\Base\BaseModelInterface;

/**
 * CmLogAiUsagesModel - Centralized AI Token Usage & Cost Analytics Hub 📊🤖⚡
 * 
 * RBN 3.5: Shared operational layer for tracking AI prompts, token counts and costs in rbncore_common.
 * 
 * @property int         $id
 * @property string      $project_key
 * @property string      $task_key
 * @property string      $request_type
 * @property string      $ai_model
 * @property int         $prompt_tokens
 * @property int         $output_tokens
 * @property int         $total_tokens
 * @property float       $estimated_cost_usd
 * @property string      $key_type
 * @property string      $created_at
 * @property string|null $updated_at
 */
class CmLogAiUsagesModel extends BaseModel implements BaseModelInterface
{
    protected string $connection = 'database_common';
    protected $table = 'cm_log_ai_usages';
    protected bool $timestamps = true;
    protected bool $scoped = true;
}
