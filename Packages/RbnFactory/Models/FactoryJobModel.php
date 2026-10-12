<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnFactory\Models;

use Rbn\Framework\Core\Base\Attributes\Component;
use Rbn\Framework\Core\Base\Data\BaseModel;
use Rbn\Framework\Core\Support\Contracts\Base\BaseModelInterface;

/**
 * FactoryJobModel - İçerik fabrikası takip satırı (bir taslağın VPS'e sunumu ve yayın durumu) 🏭
 * Tablo adı projenin sitenin `Core/Tasks/<ProjectKey>Factory.php` dosyasındaki `tables.jobs` ayarından gelir.
 *
 * @property int         $id
 * @property string      $project_key
 * @property string      $job_no
 * @property int|null    $draft_id         yenileme işinde null
 * @property int|null    $post_id
 * @property string      $content_type
 * @property string      $kind             new|renew (renew = mevcut yazının yenilenmesi; post_id = yenilenen yazı)
 * @property string|null $renew_payload    renew: teslim edilmiş, yayın saatini bekleyen yeni içerik (JSON)
 * @property string      $job_status       pending|delivered|failed|permanent_failure
 * @property string|null $error_message
 * @property string|null $failed_at
 * @property int         $retry_count
 * @property string|null $draft_title
 * @property int|null    $draft_category_id
 * @property string|null $draft_deleted_at
 * @property string      $publish_status   pending|published|incomplete
 * @property string|null $scheduled_at
 * @property string|null $last_checked_at
 * @property string|null $missing_reason
 * @property string      $requested_at
 * @property string|null $delivered_at
 * @property string|null $published_at
 */
#[Component(alias: 'factory.job', type: 'model')]
class FactoryJobModel extends BaseModel implements BaseModelInterface
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';
    public const STATUS_PERMANENT_FAILURE = 'permanent_failure';

    public const KIND_NEW = 'new';
    public const KIND_RENEW = 'renew';

    public const PUBLISH_PENDING = 'pending';
    public const PUBLISH_PUBLISHED = 'published';
    public const PUBLISH_INCOMPLETE = 'incomplete';

    protected string $connection = 'database_project';

    /**
     * @tenant-scope multi-tenant-ready
     * Kiracı kolonu (project_key) var; kapsam sorguda ve yazmada `withProjectScope()` ile açık anahtara bağlanır.
     */
    protected bool $scoped = true;

    protected bool $timestamps = false;

    public function __construct()
    {
        parent::__construct();
        $this->table = $this->handler('factoryConfig')->table('jobs');
    }
}
