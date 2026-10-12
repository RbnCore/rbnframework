<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Models\Project;

use Rbn\Framework\Core\Base\Data\BaseModel;
use Rbn\Framework\Core\Support\Contracts\Base\BaseModelInterface;

/**
 * ContentRedirectModel - Eski içerik adreslerinin kalıcı (301) yönlendirme kaydı 🧭
 * Bir yazının slug'ı ya da kategorisi değiştiğinde eski adres buraya yazılır; eski adres yazının GÜNCEL adresine yönlenir.
 *
 * @property int         $id
 * @property string      $content_type        blog | news
 * @property int         $post_id
 * @property string      $old_slug
 * @property string      $old_category_slug   kategorisiz adres için boş metin
 * @property string      $created_at
 */
class ContentRedirectModel extends BaseModel implements BaseModelInterface
{
    /** @var string Veritabanı Bağlantısı */
    protected string $connection = 'database_project';

    /** @var string Veritabanı Tablosu */
    protected $table = 'app_content_redirects';

    /**
     * Kiracı kolonu yok: tablo projenin kendi veritabanında, projeye özeldir.
     *
     * @tenant-scope single-tenant-now
     */
    protected bool $scoped = false;

    /** Tabloda yalnız created_at var; elle yazılır. */
    protected bool $timestamps = false;
}
