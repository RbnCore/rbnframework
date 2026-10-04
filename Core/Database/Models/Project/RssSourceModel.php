<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Models\Project;

use Rbn\Framework\Core\Base\Data\BaseModel;
use Rbn\Framework\Core\Support\Contracts\Base\BaseModelInterface;

/**
 * RssSourceModel - Evrensel Proje RSS Kaynak Modeli 🛰️
 * Tüm projelerin RSS besleme kaynaklarını (URL, isim, aktiflik) yönetir.
 * RBN 3.5 Masterpiece Standard.
 * 
 * @property int         $id
 * @property string      $project_key
 * @property string      $name
 * @property string      $url
 * @property int|null    $category_id
 * @property int         $is_active
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class RssSourceModel extends BaseModel implements BaseModelInterface
{
    /** @var string Veritabanı Bağlantısı */
    protected string $connection = 'database_project';

    /** @var string Veritabanı Tablosu */
    protected $table = 'app_rss_sources';
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
