<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Repositories\Master;

use Rbn\Framework\Core\Base\Data\BaseRepository;

/**
 * MasterProjectsRepository - Master Projects Data Access & Query Repository 🌍🏛️⚓
 * 
 * RBN 3.5 Masterpiece: Enterprise Repository Pattern for Master Projects.
 * Located strictly under Core\Database\Repositories\Master for clean architecture.
 * 
 * @property \Rbn\Framework\Core\Database\Models\Master\MasterProjectsModel $masterProjectsModel
 */
class MasterProjectsRepository extends BaseRepository
{
    /** @var string Target primary model alias */
    protected $targetModel = 'master.projects';

    /**
     * Proje anahtarının benzersiz olup olmadığını kontrol eder 🔍
     */
    public function isKeyUnique(string $projectKey, ?int $excludeId = null): bool
    {
        $query = $this->model('master.projects')->where('project_key', $projectKey);
        
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->first() === null;
    }

    /**
     * Lisans anahtarının benzersiz olup olmadığını kontrol eder 🔍
     */
    public function isLicenseKeyUnique(string $licenseKey, ?int $excludeId = null): bool
    {
        $query = $this->model('master.projects')->where('license_key', $licenseKey);
        
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->first() === null;
    }

    /**
     * Gruba göre tüm aktif projeleri döner 🏛️🔍
     */
    public function getProjectsByGroup(string $groupName): array
    {
        return $this->model('master.projects')
            ->where('project_group', $groupName)
            ->where('status', 'active')
            ->get()
            ->all();
    }

    /* ==========================================================================
       [ YETKİLİ PROJE KİMLİĞİ YAZICI ] 🔐🏛️  (FW-BASE-2 T4)
       --------------------------------------------------------------------------
       `MasterProjectsModel::$guarded = ['license_key', 'status']` olduğu için
       genel `create()`/`update()` bu iki alanı düşürür. Proje kaydı ve
       güncellemesi meşru olarak bu alanları YAZMALI; bu yüzden açık, loglanan
       ve ismi ne yaptığını söyleyen iki metot var.
       ========================================================================== */

    /**
     * Yeni proje kimliği yazar (lisans anahtarı + durum yetkili). 🔐
     *
     * `license_key` sunucuda üretilmiş olmalıdır; `status` beyaz listeden
     * geçer. Ham kullanıcı girdisi bu metoda GİRMEZ — çağıran kararı verir.
     */
    public function createProjectIdentity(array $data): int
    {
        return (int) $this->model('master.projects')
            ->authorizeFields(['license_key', 'status'])
            ->create($data);
    }

    /**
     * Proje kimliğini günceller (lisans anahtarı + durum yetkili). 🔐
     */
    public function updateProjectIdentity(int $id, array $data): bool
    {
        if ($id <= 0) {
            return false;
        }

        return (bool) $this->model('master.projects')
            ->authorizeFields(['license_key', 'status'])
            ->update($id, $data);
    }
}
