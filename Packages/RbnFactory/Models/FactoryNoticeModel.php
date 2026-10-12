<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnFactory\Models;

use Rbn\Framework\Core\Base\Attributes\Component;
use Rbn\Framework\Core\Base\Data\BaseModel;
use Rbn\Framework\Core\Support\Contracts\Base\BaseModelInterface;

/**
 * FactoryNoticeModel - Site sahibine giden bildirimlerin gönderim kaydı (tekrar bastırma için) 🔔
 *
 * @property int         $id
 * @property string      $project_key
 * @property string      $event_key
 * @property string      $dedup_key
 * @property int         $ok
 * @property string|null $detail
 * @property string      $sent_at
 */
#[Component(alias: 'factory.notice', type: 'model')]
class FactoryNoticeModel extends BaseModel implements BaseModelInterface
{
    protected string $connection = 'database_project';

    /**
     * @tenant-scope multi-tenant-ready
     */
    protected bool $scoped = true;

    protected bool $timestamps = false;

    public function __construct()
    {
        parent::__construct();
        $this->table = $this->handler('factoryConfig')->table('notices');
    }
}
