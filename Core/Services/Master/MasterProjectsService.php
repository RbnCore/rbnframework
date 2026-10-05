<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Master;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\Support\Bridges\Helpers\Library\Version;

/**
 * MasterProjectsService - Proje Yönetimi İş Mantığı Servisi 🏛️🛰️
 * RBN 3.5 Masterpiece Standard.
 * 
 * @property \Rbn\Framework\Core\Database\Repositories\Master\MasterProjectsRepository $masterProjectsRepository
 */
class MasterProjectsService extends BaseService
{
    /**
     * The primary model target for this service 🎯
     */
    protected $targetModel = 'master.projects';

    /**
     * Yeni proje oluşturur 🚀
     */
    public function createProject(array $data): array
    {
        // 1. Proje anahtarını (project_key) proje adından otomatik oluştur
        $projectKey = $this->helper('text')->turkishSlug($data['project_name']);
        $data['project_key'] = $projectKey;

        // 2. Benzersizlik kontrolü
        if (!$this->masterProjectsRepository->isKeyUnique($data['project_key'])) {
            return [
                'success' => false,
                'errors' => ['Proje anahtarı zaten kullanımda.']
            ];
        }

        // 3. Lisans Anahtarını otomatik oluştur
        $group = $data['project_group'] ?? $data['project_key'] ?? null;
        $data['license_key'] = $this->generateUniqueLicenseKey($group);

        // 4. Özel Dosya Yolunu (custom_path) formatla
        $data['custom_path'] = $this->formatCustomPath($data['custom_path'] ?? null, $projectKey);

        // FW-BASE-2 (T4): `license_key` ve `status` korumali alan; yazma
        // yetkili yoldan (`createProjectIdentity`) gecer.
        $res = $this->masterProjectsRepository->createProjectIdentity([
            'project_key' => $data['project_key'],
            'project_name' => $data['project_name'],
            'domain' => $data['domain'] ?: null,
            'status' => $data['status'] ?: 'active',
            'custom_path' => $data['custom_path'],
            // [FW-SURUMLEME-2] Proje surumu TEK KAYNAK: master DB `projects.version`.
            // Burada yazilan deger master tablosunun kendisidir; gecersiz iki
            // parcali `1.0` gibi bir varsayilan YAZILMAZ — standart baslangic
            // surumu kullanilir (versioning.md §1).
            'version' => $data['version'] ?: Version::initial(),
            'license_key' => $data['license_key'],
        ]);

        if ($res) {
            $group = $data['project_group'] ?? $data['project_key'] ?? null;
            $this->clearDiscoveryCache($data['project_key'], $data['domain'] ?? null, $group);
        }

        return [
            'success' => (bool) $res,
            'errors' => $res ? [] : ['Proje oluşturulamadı.']
        ];
    }

    /**
     * Proje günceller 📝
     */
    public function updateProject(int $id, array $data): array
    {
        $existing = $this->masterProjectsRepository->find($id);
        if (!$existing) {
            return [
                'success' => false,
                'errors' => ['Proje bulunamadı.']
            ];
        }

        // 1. Proje anahtarını (project_key) proje adından otomatik oluştur/güncelle
        $projectKey = $this->helper('text')->turkishSlug($data['project_name']);
        $data['project_key'] = $projectKey;

        // 2. Benzersizlik kontrolü (başka bir projenin key'i ile çakışmasın)
        if (!$this->masterProjectsRepository->isKeyUnique($data['project_key'], $id)) {
            return [
                'success' => false,
                'errors' => ['Proje anahtarı zaten kullanımda.']
            ];
        }

        // 3. Lisans Anahtarını koru (yoksa oluştur)
        $licenseKey = $existing['license_key'] ?? '';
        if (empty($licenseKey)) {
            $group = $existing['project_group'] ?? $data['project_group'] ?? $existing['project_key'] ?? null;
            $licenseKey = $this->generateUniqueLicenseKey($group);
        }
        $data['license_key'] = $licenseKey;

        // 4. Özel Dosya Yolunu (custom_path) formatla
        $data['custom_path'] = $this->formatCustomPath($data['custom_path'] ?? null, $projectKey);

        // FW-BASE-2 (T4): yetkili yol (bkz. `createProjectIdentity`).
        $res = $this->masterProjectsRepository->updateProjectIdentity($id, [
            'project_key' => $data['project_key'],
            'project_name' => $data['project_name'],
            'domain' => $data['domain'] ?: null,
            'status' => $data['status'] ?: 'active',
            'custom_path' => $data['custom_path'],
            'version' => $data['version'] ?: Version::initial(),
            'license_key' => $data['license_key'],
        ]);

        if ($res) {
            $group = $existing['project_group'] ?? $data['project_group'] ?? $existing['project_key'] ?? null;
            $this->clearDiscoveryCache($data['project_key'], $data['domain'] ?? null, $group);
        }

        return [
            'success' => $res,
            'errors' => $res ? [] : ['Güncelleme başarısız oldu.']
        ];
    }

    /**
     * Benzersiz Lisans Anahtarı üretir 🔑
     * Format: RBN-[TIER]-[GROUP]-[HASH6]-[YEAR] (Örn: RBN-FREE-APPS-A8F2B1-2026)
     */
    private function generateUniqueLicenseKey(?string $group = null, string $tier = 'FREE'): string
    {
        $groupPrefix = !empty($group) ? strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $group)) : 'CORE';
        $year = date('Y');

        do {
            $hash = strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 6));
            $key = "RBN-{$tier}-{$groupPrefix}-{$hash}-{$year}";
        } while (!$this->masterProjectsRepository->isLicenseKeyUnique($key));

        return $key;
    }

    /**
     * Özel Dosya Yolunu 'projects/' ön eki olmadan formatlar 📁
     */
    private function formatCustomPath(?string $path, string $projectKey): string
    {
        $path = trim($path ?? '');
        if ($path === '') {
            return $projectKey;
        }

        // Strip leading/trailing slashes
        $path = ltrim($path, '/\\');

        // Strip pre-existing 'projects/' to avoid saving it in DB
        if (str_starts_with($path, 'projects/')) {
            $path = substr($path, 9);
        } elseif (str_starts_with($path, 'projects\\')) {
            $path = substr($path, 9);
        }

        return trim($path, '/\\');
    }

    /**
     * Belirli bir gruba ait tüm aktif projeleri getirir 🏛️🛰️
     */
    public function getProjectsByGroup(string $groupName): array
    {
        if (empty($groupName)) {
            return [];
        }

        return $this->MasterProjectsProvider ? $this->MasterProjectsProvider->getProjectsByGroup($groupName) : [];
    }

    /* ==========================================================================
       [ PROJE SURUMU: SAYAÇ ] (FW-SURUMLEME-2)
       --------------------------------------------------------------------------
       `projects.version` TEK kaynaktir. Surum ELLE yazilmaz: `Version::next()`
       hesaplar. Varsayilan KURU KOSUDUR (`$apply = false`); yazma yalniz
       acikca `--apply` ile yapilir ve yalniz repository uzerinden gecer
       (Anayasa §8: DB'ye dogrudan erisim yok).
       ========================================================================== */

    /**
     * Bir projenin siradaki surumunu hesaplar (ve istenirse yazar).
     *
     * @return array{success:bool, project_key:string, current:string, next:string, written:bool, errors:array<int,string>}
     */
    public function nextVersion(string $projectKey, bool $apply = false): array
    {
        $projectKey = trim($projectKey);
        $sonuc = [
            'success' => false, 'project_key' => $projectKey,
            'current' => '', 'next' => '', 'written' => false, 'errors' => [],
        ];

        if ($projectKey === '') {
            $sonuc['errors'][] = 'Proje anahtarı zorunludur.';
            return $sonuc;
        }

        $repo = $this->masterProjectsRepository;

        $satir = $repo->findByProjectKey($projectKey);
        if ($satir === null) {
            $sonuc['errors'][] = 'Proje bulunamadı: ' . $projectKey;
            return $sonuc;
        }

        $mevcut = trim((string) ($satir['version'] ?? ''));
        // Gecersiz kayit varsa guvenli baslangica dusulur; sayac ORADAN devam eder.
        $sonuc['current'] = Version::isValid($mevcut) ? $mevcut : Version::initial();
        $sonuc['next'] = Version::next($sonuc['current']);

        if (!$apply) {
            $sonuc['success'] = true;
            return $sonuc;
        }

        try {
            $yazildi = $repo->updateProjectVersion((int) $satir['id'], $sonuc['next']);
        } catch (\InvalidArgumentException $e) {
            $sonuc['errors'][] = $e->getMessage();
            return $sonuc;
        }

        $sonuc['written'] = $yazildi;
        $sonuc['success'] = $yazildi;

        if (!$yazildi) {
            $sonuc['errors'][] = 'Sürüm yazılamadı.';
        } else {
            // Onbellek temizlenir; aksi halde eski surum okunmaya devam eder.
            $this->clearDiscoveryCache(
                $projectKey,
                $satir['domain'] ?? null,
                $satir['project_group'] ?? $projectKey
            );
        }

        return $sonuc;
    }

    /**
     * Clear the Project Discovery cache file 🧹
     */
    private function clearDiscoveryCache(?string $projectKey, ?string $domain, ?string $group = null): void
    {
        if (class_exists(\Rbn\Framework\Core\System\Storage\Providers\BootCacheProvider::class)) {
            $publicPath = class_exists(\Rbn\Framework\Core\System\Paths\Paths::class)
                ? \Rbn\Framework\Core\System\Paths\Paths::publicRoot()
                : null;

            if (!empty($projectKey)) {
                \Rbn\Framework\Core\System\Storage\Providers\BootCacheProvider::delete($projectKey, $publicPath, 'project_');
            }

            if (!empty($domain)) {
                $domainKey = str_replace(['.test', '.local'], '', explode(':', $domain)[0]);
                \Rbn\Framework\Core\System\Storage\Providers\BootCacheProvider::delete($domainKey, $publicPath, 'domain_');
            }

            if (!empty($group)) {
                \Rbn\Framework\Core\System\Storage\Providers\BootCacheProvider::delete($group, $publicPath, 'group_');
            }
        }
    }
}
