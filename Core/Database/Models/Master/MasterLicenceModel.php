<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Models\Master;

use Rbn\Framework\Core\Base\Data\BaseModel;
use Rbn\Framework\Core\Support\Contracts\Base\BaseModelInterface;

/**
 * MasterLicenceModel - Merkezi Lisans Kaydi Modeli 🔑🏛️⚓
 *
 * TEK lisans tablosu: hem proje hem uygulama lisansini tutar.
 * `subject_type` ('project' | 'application') + `subject_id` ile sahip ayrilir;
 * ayri bir `application_licenses` tablosu YOKTUR (patron karari).
 *
 * Yazim kurali: `Licence` (Ingiliz yazimi) HER YERDE — tablo `licences`,
 * sutun `licence_key`, sinif `MasterLicenceRepository`.
 *
 * @property int    $id
 * @property string $licence_key
 * @property string $subject_type
 * @property int    $subject_id
 * @property string $tier (FREE, LIFETIME, PRO)
 * @property string $status (active, suspended, revoked, expired)
 * @property string $expires_at
 * @property int    $max_activations
 * @property string $device_hash
 * @property string $notes
 * @property string $created_at
 * @property string $updated_at
 */
class MasterLicenceModel extends BaseModel implements BaseModelInterface
{
    protected string $connection = 'database_master';
    protected $table = 'licences';
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

    /**
     * FW-BASE-2 (T4) — korumalı alan kara listesi 🛡️
     *
     * `status` yalnız beyaz listeli bir sabitten (`MasterLicenceConfig`) gelir
     * ve `MasterLicenceRepository::updateStatus()` / `create()` yetkili
     * yollarıyla yazılır. Lisans iptali ham veriden yapılamaz.
     */
    protected array $guarded = ['status'];
}
