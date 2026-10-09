<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Models\Master;

use Rbn\Framework\Core\Base\Data\BaseModel;
use Rbn\Framework\Core\Support\Contracts\Base\BaseModelInterface;

/**
 * MasterProjectsModel - RBN Framework Project Identity Model 🏛️🧬⚓
 * 
 * RBN Framework: Strategic Master Hub Model.
 * This model lives in the Database Master layer to manage project registrations across the ecosystem.
 * All master-layer registrations MUST flow through this authoritative shell.
 * 
 * @property int    $id
 * @property string $project_key
 * @property string $project_name
 * @property string $domain
 * @property string $status (active, maintenance, suspended)
 * @property string $custom_path
 * @property string $version
 * @property string $license_key
 * @property string $created_at
 * @property string $updated_at
 */
class MasterProjectsModel extends BaseModel implements BaseModelInterface
{
    protected string $connection = 'database_master';
    protected $table = 'projects';
    /**
     * FW-ALTYAPI-2 H / G1 - kiraci izolasyonu BEYANI (beyan zorunlulugu).
     *
     * Kapsam  : kiraci KAYDI tablosu.
     * Gerekce: tablonun kendisi kiraci KAYDIDIR: kolon satyrin konusudur, aktif kiraci degil; kapsam buraya uygulanamaz.
     *
     * Varsayilan `BaseModel::$scoped` DEGISTIRILMEDI: kiraci izolasyonu
     * varsayilan olarak KAPALI kalir (acmak girisi 7 veritabaninda
     * kirar - FW-ALTYAPI-1 H 4.2 olculdu). G1 yalniz BEYAN ZORUNLULUGU
     * getirir: her model kararini kendi dosyasinda yazar.
     *
     * @tenant-scope tenant-record
     */
    protected bool $scoped = false;
    protected bool $timestamps = true;

    /**
     * FW-BASE-2 (T4) — korumalı alan kara listesi 🛡️
     *
     * `license_key` yalnız sunucuda üretilir (`generateUniqueLicenseKey()`),
     * `status` ise servis kararıdır; ikisi de ham veriden yazılamaz. Proje
     * kaydı/güncellemesi `MasterProjectsRepository::createProjectIdentity()` /
     * `updateProjectIdentity()` yetkili yollarından geçer.
     */
    protected array $guarded = ['license_key', 'status'];
}
