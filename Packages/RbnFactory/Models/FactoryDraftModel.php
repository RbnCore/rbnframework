<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnFactory\Models;

use Rbn\Framework\Core\Base\Attributes\Component;
use Rbn\Framework\Core\Base\Data\BaseModel;
use Rbn\Framework\Core\Support\Contracts\Base\BaseModelInterface;

/**
 * FactoryDraftModel - Fabrikanın konu kaynağı olan içerik taslakları (okunur, yayında silinir) 📝
 * Tablo adı projenin sitenin `Core/Tasks/<ProjectKey>Factory.php` dosyasındaki `tables.drafts` ayarından gelir.
 *
 * @property int    $id
 * @property string $project_key
 * @property string $type
 * @property int    $category_id
 * @property string $title
 * @property string $slug
 * @property int    $order_num
 */
#[Component(alias: 'factory.draft', type: 'model')]
class FactoryDraftModel extends BaseModel implements BaseModelInterface
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
        $this->table = $this->handler('factoryConfig')->table('drafts');
    }
}
