<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Models\Project;

use Rbn\Framework\Core\Base\Data\BaseModel;
use Rbn\Framework\Core\Support\Contracts\Base\BaseModelInterface;
use Rbn\Framework\Core\Base\Attributes\Component;

/**
 * ContentDraftModel - Evrensel İçerik Taslak ve Fikir Modeli 📝🏛️⚓
 * Tüm projelerin içerik taslaklarını tek merkezden yönetir.
 * RBN 3.5 Masterpiece Standard.
 * 
 * @property int    $id
 * @property string $project_key
 * @property string $type
 * @property int    $category_id
 * @property string $title
 * @property string $slug
 * @property int    $is_generated
 * @property int    $is_rewrite
 * @property string $created_at
 * @property string $updated_at
 */
class ContentDraftModel extends BaseModel implements BaseModelInterface
{
    /** @var string Veritabanı Bağlantısı */
    protected string $connection = 'database_project';

    /** @var string Veritabanı Tablosu */
    protected $table = 'app_content_drafts';
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
