<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Models\Master;

use Rbn\Framework\Core\Base\Data\BaseModel;
use Rbn\Framework\Core\Support\Contracts\Base\BaseModelInterface;

/**
 * MasterApplicationModel - Dagitilabilir Uygulama Kaydi Modeli 📦🏛️⚓
 *
 * URUN surumu buradadir (`current_version`); LISANS surumu burada degildir —
 * lisans tablosunda surum kolonu YOKTUR (patron karari).
 *
 * @property int    $id
 * @property string $app_key
 * @property string $name
 * @property string $platform (windows, macos, linux)
 * @property string $channel (stable, beta)
 * @property string $status (active, passive, retired)
 * @property string $current_version
 * @property string $download_url
 * @property string $sha256
 * @property string $min_version
 * @property string $description
 * @property string $created_at
 * @property string $updated_at
 */
class MasterApplicationModel extends BaseModel implements BaseModelInterface
{
    protected string $connection = 'database_master';
    protected $table = 'applications';
    /**
     * FW-ALTYAPI-2 H / G1 - kiraci izolasyonu BEYANI (beyan zorunlulugu).
     *
     * Kapsam  : master yonetim duzlemi.
     * Gerekce: master yonetim duzlemi: satir bir KIRACI degil, sistem kaydidir; kiraci bazli kapsam bu duzlemde uygulanmaz.
     *
     * Varsayilan `BaseModel::$scoped` DEGISTIRILMEDI: kiraci izolasyonu
     * varsayilan olarak KAPALI kalir (acmak girisi 7 veritabaninda
     * kirar - FW-ALTYAPI-1 H 4.2 olculdu). G1 yalniz BEYAN ZORUNLULUGU
     * getirir: her model kararini kendi dosyasinda yazar.
     *
     * @tenant-scope master
     */
    protected bool $scoped = false;
    protected bool $timestamps = true;
}
