<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Master;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\Services\Master\Data\MasterLicenceConfig;

/**
 * MasterLicencesService - Merkezi Lisans Orkestratörü 🔑🏛️
 * Yalnızca karar (policy) katmanıdır; veritabanına DOĞRUDAN erişim YOKTUR (yalnız repository).
 * FAIL-CLOSED: bilinmeyen anahtar, dondurulmuş/iptal kayıt ve DB hatası GEÇERSİZ sonuç verir.
 *
 * @property \Rbn\Framework\Core\Database\Repositories\Master\MasterLicenceRepository $masterLicenceRepository
 */
class MasterLicencesService extends BaseService
{
    /** Anahtar üretiminde en fazla denenecek benzersizlik denemesi 🔁 */
    private const MAX_KEY_ATTEMPTS = 5;

    /** @var object|null Test/entegrasyon için açık repository yuvası 🧪 */
    protected ?object $licenceRepository = null;

    /** Repository'yi dışarıdan verir (birim testi veya özel bağlama). */
    public function setLicenceRepository(object $repository): void
    {
        $this->licenceRepository = $repository;
    }
    /** Aktif repository: önce verilen yuva, yoksa keşif (magic) yolu. */
    private function repo(): object
    {
        return $this->licenceRepository ?: $this->masterLicenceRepository;
    }

    /**
     * Lisans anahtarını doğrular 🔍
     * expires_at NULL ise süresizdir; süresi dolan kayıtta status ALANI değişmez, yalnız sonuç döner.
     * @return array{valid: bool, status: ?string, tier: ?string, expires_at: ?string, reason: ?string}
     */
    public function verify(string $key, ?string $deviceHash = null): array
    {
        $key = trim($key);

        // 1. Boş anahtar hiçbir zaman geçerli sayılmaz.
        if ($key === '') {
            return $this->result(false, null, null, MasterLicenceConfig::REASON_UNKNOWN_KEY);
        }
        // 2. Kaydı oku: veritabanı hatası da geçersiz sonuç döner.
        try {
            $row = $this->repo()->findByKey($key);
        } catch (\Throwable $e) {
            $this->warn('Lisans anahtarı okunamadı: ' . $e->getMessage());
            return $this->result(false, null, null, MasterLicenceConfig::REASON_STORAGE_ERROR);
        }

        if (!is_array($row) || $row === []) {
            return $this->result(false, null, null, MasterLicenceConfig::REASON_UNKNOWN_KEY);
        }

        $status = trim((string)($row['status'] ?? ''));
        $tier = trim((string)($row['tier'] ?? ''));
        $expiresAt = $row['expires_at'] ?? null;

        // 3. NULL/boş alanlar ACTIVE/FREE VARSAYILMAZ (fail-closed).
        if (!LicenceAccessRule::isRecordUsable($row)) {
            return $this->result(false, $status, $tier, MasterLicenceConfig::REASON_INVALID_RECORD, $expiresAt);
        }

        // 4. İptal / dondurma / süresi dolmuş durumu: TARİHTEN BAĞIMSIZ geçersiz
        //    (tek kural: LicenceAccessRule; `expires_at` NULL veya gelecekte olsa bile).
        if (($sebep = LicenceAccessRule::blockingReason($status)) !== null) {
            return $this->result(false, $status, $tier, $sebep, $expiresAt);
        }

        // 5. Süre kontrolü (TEK kural: LicenceAccessRule; bozuk tarih = dolmuş).
        if (LicenceAccessRule::isExpired($expiresAt)) {
            return $this->result(false, $status, $tier, MasterLicenceConfig::REASON_EXPIRED, $expiresAt);
        }
        // 6. Cihaz kuralı (proje lisansında hash gelmeyebilir).
        if ($deviceHash !== null && $deviceHash !== '' && !$this->checkDevice($row, $deviceHash)) {
            return $this->result(false, $status, $tier, MasterLicenceConfig::REASON_DEVICE_MISMATCH, $expiresAt);
        }

        return $this->result(true, $status, $tier, null, $expiresAt);
    }

    /** Bir özneye (proje/uygulama) ait lisansı döndürür 🎯 */
    public function forSubject(string $type, int $id): ?array
    {
        if ($id <= 0 || !in_array($type, self::subjectTypes(), true)) {
            return null;
        }

        try {
            $row = $this->repo()->findForSubject($type, $id);
        } catch (\Throwable $e) {
            $this->warn('Özne lisansı okunamadı: ' . $e->getMessage());
            return null;
        }

        return is_array($row) && $row !== [] ? $row : null;
    }

    /**
     * Yeni lisans anahtarı üretir ve kaydeder 🆗
     * @return array{success: bool, id: int, licence_key: ?string, errors: array<int,string>}
     */
    public function issue(string $type, int $id, string $tier, ?string $expiresAt): array
    {
        $failResult = function (string $message): array {
            return ['success' => false, 'id' => 0, 'licence_key' => null, 'errors' => [$message]];
        };
        if ($id <= 0 || !in_array($type, self::subjectTypes(), true)) {
            return $failResult('Geçersiz lisans öznesi.');
        }
        $tier = strtoupper(trim($tier));
        if (!in_array($tier, self::tiers(), true)) {
            return $failResult('Geçersiz lisans kademesi.');
        }
        // TEKIL ÖZNE (Y-2): bir özneye (subject_type + subject_id) yalnız bir
        // lisans satırı yazılır. `licences` tablosundaki UNIQUE anahtar da aynı
        // kuralı zorlar; burada kullanıcıya anlamlı bir hata döner.
        if ($this->forSubject($type, $id) !== null) {
            return $failResult('Bu özneye ait lisans zaten mevcut.');
        }
        $key = $this->generateKey();
        if ($key === null) {
            return $failResult('Benzersiz lisans anahtarı üretilemedi.');
        }

        try {
            $newId = (int)$this->repo()->create([
                'licence_key'     => $key,
                'subject_type'    => $type,
                'subject_id'      => $id,
                'tier'            => $tier,
                'status'          => MasterLicenceConfig::STATUS_ACTIVE,
                'expires_at'      => ($expiresAt === '' ? null : $expiresAt),
                'max_activations' => MasterLicenceConfig::DEFAULT_MAX_ACTIVATIONS,
                'device_hash'     => null,
                'notes'           => null,
            ]);
        } catch (\Throwable $e) {
            $this->warn('Lisans kaydedilemedi: ' . $e->getMessage());
            return $failResult('Lisans kaydedilemedi.');
        }

        return $newId > 0
            ? ['success' => true, 'id' => $newId, 'licence_key' => $key, 'errors' => []]
            : $failResult('Lisans oluşturulamadı.');
    }

    /** Lisansı iptal eder (status = revoked) 🚫 */
    public function revoke(string $key): bool
    {
        $key = trim($key);
        if ($key === '') {
            return false;
        }

        try {
            $row = $this->repo()->findByKey($key);
            if (!is_array($row) || !isset($row['id'])) {
                return false;
            }

            return (bool)$this->repo()->updateStatus((int)$row['id'], MasterLicenceConfig::STATUS_REVOKED);
        } catch (\Throwable $e) {
            $this->warn('Lisans iptali yazılamadı: ' . $e->getMessage());
            return false;
        }
    }

    /** Cihaz kuralı: boş device_hash ilk doğrulamada bağlanır; dolu ve farklıysa max_activations sınırı uygulanır. */
    private function checkDevice(array $row, string $deviceHash): bool
    {
        $bound = $row['device_hash'] ?? null;

        if ($bound === null || $bound === '') {
            try {
                return (bool)$this->repo()->bindDevice((int)$row['id'], $deviceHash);
            } catch (\Throwable $e) {
                $this->warn('Cihaz bağlanamadı: ' . $e->getMessage());
                return false;
            }
        }

        if ((string)$bound === $deviceHash) {
            return true;
        }
        // Başka cihaz geldiyse: aktivasyon sınırı 1'den büyükse izin ver.
        return (int)($row['max_activations'] ?? MasterLicenceConfig::DEFAULT_MAX_ACTIVATIONS) > 1;
    }

    /** Güçlü ve benzersiz anahtar üretir (çakışmada en çok 5 deneme). Biçim: RBN-XXXX-XXXX-XXXX-XXXX (hex). */
    private function generateKey(): ?string
    {
        for ($attempt = 0; $attempt < self::MAX_KEY_ATTEMPTS; $attempt++) {
            $key = MasterLicenceConfig::KEY_PREFIX . '-'
                . implode('-', str_split(strtoupper(bin2hex(random_bytes(8))), 4));
            try {
                if ($this->repo()->isKeyUnique($key)) {
                    return $key;
                }
            } catch (\Throwable $e) {
                $this->warn('Anahtar benzersizliği denetlenemedi: ' . $e->getMessage());
                return null;
            }
        }

        return null; // 5 deneme sonunda benzersiz anahtar bulunamadı.
    }

    /** Doğrulama sonucunu standart biçimde üretir. */
    private function result(bool $valid, ?string $status, ?string $tier, ?string $reason, ?string $expiresAt = null): array
    {
        return [
            'valid'      => $valid,
            'status'     => $status,
            'tier'       => $tier,
            'expires_at' => $expiresAt,
            'reason'     => $reason,
        ];
    }

    /** Uyarı günlüğü; loglama katmanı güvenlik kararını ASLA değiştirmez. */
    private function warn(string $message): void
    {
        try {
            $this->logs()?->channel('licence')->warning($message);
        } catch (\Throwable $e) {
            // Loglama altyapısı yoksa sessizce geç.
        }
    }

    /** @return array<int,string> Geçerli özne türleri */
    private static function subjectTypes(): array
    {
        return [MasterLicenceConfig::SUBJECT_PROJECT, MasterLicenceConfig::SUBJECT_APPLICATION];
    }

    /** @return array<int,string> Geçerli kademeler */
    private static function tiers(): array
    {
        return [MasterLicenceConfig::TIER_FREE, MasterLicenceConfig::TIER_LIFETIME, MasterLicenceConfig::TIER_PRO];
    }
}