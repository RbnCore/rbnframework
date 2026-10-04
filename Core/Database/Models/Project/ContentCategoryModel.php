<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Models\Project;

use Rbn\Framework\Core\Base\Data\BaseModel;
use Rbn\Framework\Core\Support\Contracts\Base\BaseModelInterface;
use Rbn\Framework\Core\Base\Attributes\Component;

/**
 * ContentCategoryModel - Evrensel İçerik Kategorisi Modeli 🗂️✨
 * Tüm projelerin ve içerik türlerinin (blog, news, program vb.) kategorilerini tek merkezden yönetir.
 * RBN 3.5 Masterpiece Standard.
 * 
 * @property int    $id
 * @property string $project_key
 * @property string $type
 * @property string $name
 * @property string $slug
 * @property string $icon
 * @property int    $order_num
 * @property string $description
 * @property int    $is_active
 * @property string $created_at
 * @property string $updated_at
 */
class ContentCategoryModel extends BaseModel implements BaseModelInterface
{
    /**
     * FW-BASE-3 (T5): beyaz liste — yalnız bu alanlar toplu atamayla yazılabilir.
     *
     * Liste panelin GERÇEKTEN gönderdiği alanların tamamını kapsar
     * (`ProductCategoriesController::save()` ve `ProgramCategoryController::save()`
     * → `ContentCategoryRepository::saveCategory()`). `type` ve `project_key`
     * servislerde sunucu tarafında sabitlenir ama yazılabilir olmalıdır.
     * Zaman damgaları motor tarafından yazılır.
     *
     * @var string[]
     */
    protected array $fillable = [
        'project_key', 'type', 'name', 'slug', 'icon', 'order_num', 'description', 'is_active',
    ];

    /** @var string Veritabanı Bağlantısı */
    protected string $connection = 'database_project';

    /** @var string Veritabanı Tablosu */
    protected $table = 'app_content_categories';
    /**
     * FW-ALTYAPI-2 H / G1 - kiraci izolasyonu BEYANI (beyan zorunlulugu).
     *
     * Kapsam  : kolonu var, tablo gercekten cok-kiracili.
     * Gerekce: kiraci kolonu ZATEN var ve tablo olcumde gercekten cok-kiracili; kapsam BU DALGADA acilmaz, G4 dongusunde `true`ye cevrilecek.
     *
     * Varsayilan `BaseModel::$scoped` DEGISTIRILMEDI: kiraci izolasyonu
     * varsayilan olarak KAPALI kalir (acmak girisi 7 veritabaninda
     * kirar - FW-ALTYAPI-1 H 4.2 olculdu). G1 yalniz BEYAN ZORUNLULUGU
     * getirir: her model kararini kendi dosyasinda yazar.
     *
     * @tenant-scope multi-tenant-ready
     */
    protected bool $scoped = true;

    /** @var bool Zaman Damgası Yönetimi */
    protected bool $timestamps = true;
}
