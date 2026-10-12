<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnFactory\Models;

use Rbn\Framework\Core\Base\Attributes\Component;
use Rbn\Framework\Core\Base\Data\BaseModel;
use Rbn\Framework\Core\Support\Contracts\Base\BaseModelInterface;

/**
 * FactoryPostModel - Sitenin yazı tablosu (içerik türüne göre: `content_types.<tür>.table`) 📰
 * Fabrika yazıyı PASİF (is_active = 0) ekler; yayın denetçisi geçerse aktifleştirir.
 *
 * @property int         $id
 * @property string      $project_key
 * @property int         $category_id
 * @property string      $title
 * @property string      $slug
 * @property string|null $summary
 * @property string      $content
 * @property string|null $image
 * @property int         $is_active
 */
#[Component(alias: 'factory.post', type: 'model')]
class FactoryPostModel extends BaseModel implements BaseModelInterface
{
    protected string $connection = 'database_project';

    /**
     * @tenant-scope multi-tenant-ready
     */
    protected bool $scoped = true;

    protected bool $timestamps = false;

    /** İçerik türünün yazı tablosuna bağlanmış KOPYA döndürür (özgün nesne değişmez). */
    public function forContentType(string $contentType): static
    {
        $clone = clone $this;
        $clone->table = $this->handler('factoryConfig')->postTable($contentType);
        return $clone;
    }
}
