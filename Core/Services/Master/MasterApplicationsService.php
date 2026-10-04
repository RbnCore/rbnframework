<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Master;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\Services\Master\Data\MasterLicenceConfig;

/**
 * MasterApplicationsService - Uygulama Kayıt Servisi 📦🏛️
 * RBN 3.5 Masterpiece Standard.
 *
 * Proje ile aynı merkezi lisans sistemini paylaşır; yalnızca uygulama satırlarını
 * (applications tablosu) yönetir. Veritabanına DOĞRUDAN erişim YOKTUR.
 *
 * @property \Rbn\Framework\Core\Database\Repositories\Master\MasterApplicationRepository $masterApplicationRepository
 */
class MasterApplicationsService extends BaseService
{
    /** @var object|null Test/entegrasyon icin acik repository yuvası 🧪 */
    protected ?object $applicationRepository = null;

    /**
     * Repository'yi disaridan verir (birim testi veya özel bağlama).
     */
    public function setApplicationRepository(object $repository): void
    {
        $this->applicationRepository = $repository;
    }

    /**
     * Aktif repository çözümlemesi.
     */
    private function repo(): object
    {
        return $this->applicationRepository ?: $this->masterApplicationRepository;
    }

    /**
     * Uygulamayı anahtarıyla getirir 🔍
     */
    public function getByAppKey(string $key): ?array
    {
        $key = trim($key);
        if ($key === '') {
            return null;
        }

        try {
            $row = $this->repo()->findByAppKey($key);
        } catch (\Throwable $e) {
            $this->warn('Uygulama okunamadı: ' . $e->getMessage());
            return null;
        }

        return is_array($row) && $row !== [] ? $row : null;
    }

    /**
     * Tüm uygulamaları listeler 📋
     */
    public function list(): array
    {
        try {
            $rows = $this->repo()->list();
        } catch (\Throwable $e) {
            $this->warn('Uygulama listesi okunamadı: ' . $e->getMessage());
            return [];
        }

        return is_array($rows) ? $rows : [];
    }

    /**
     * Yeni uygulama kaydeder (app_key benzersiz olmak zorundadır) 🆗
     *
     * @return array{success: bool, id: int, errors: array<int,string>}
     */
    public function register(array $data): array
    {
        $failResult = ['success' => false, 'id' => 0, 'errors' => []];

        $appKey = trim((string)($data['app_key'] ?? ''));
        if ($appKey === '') {
            $failResult['errors'][] = 'Uygulama anahtarı zorunludur.';
            return $failResult;
        }

        try {
            if ($this->repo()->findByAppKey($appKey) !== null) {
                $failResult['errors'][] = 'Uygulama anahtarı zaten kullanımda.';
                return $failResult;
            }

            $id = (int)$this->repo()->create([
                'app_key'         => $appKey,
                'name'            => (string)($data['name'] ?? $appKey),
                'platform'        => $this->nullable($data['platform'] ?? null),
                'channel'         => $this->nullable($data['channel'] ?? null),
                'status'          => ($data['status'] ?? MasterLicenceConfig::STATUS_ACTIVE),
                'current_version' => ($data['current_version'] ?? null),
                'download_url'    => ($data['download_url'] ?? null),
                'sha256'          => ($data['sha256'] ?? null),
                'min_version'     => ($data['min_version'] ?? null),
                'description'     => ($data['description'] ?? null),
            ]);
        } catch (\Throwable $e) {
            $this->warn('Uygulama kaydedilemedi: ' . $e->getMessage());
            $failResult['errors'][] = 'Uygulama kaydedilemedi.';
            return $failResult;
        }

        if ($id <= 0) {
            $failResult['errors'][] = 'Uygulama oluşturulamadı.';
            return $failResult;
        }

        return ['success' => true, 'id' => $id, 'errors' => []];
    }

    /**
     * Uygulamanın güncel sürüm bilgisini günceller ⬆️
     */
    public function setCurrentVersion(string $appKey, string $version, ?string $url, ?string $sha256): bool
    {
        $appKey = trim($appKey);
        $version = trim($version);

        if ($appKey === '' || $version === '') {
            return false;
        }

        try {
            $row = $this->repo()->findByAppKey($appKey);
            if (!is_array($row) || !isset($row['id'])) {
                return false;
            }

            return (bool)$this->repo()->updateVersion((int)$row['id'], $version, $url, $sha256);
        } catch (\Throwable $e) {
            $this->warn('Uygulama sürümü güncellenemedi: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Boş string değerleri NULL yapar (veritabanı temizliği için).
     */
    private function nullable(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $metin = trim((string)$value);

        return $metin === '' ? null : $metin;
    }

    /**
     * Uyarı günlüğü yazar; loglama katmanı servisin çalışmasını engellemez.
     */
    private function warn(string $message): void
    {
        try {
            $this->logs()?->channel('licence')->warning($message);
        } catch (\Throwable $e) {
            // Loglama altyapisi yoksa sessizce gec.
        }
    }
}