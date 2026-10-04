<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Repositories\Master;

use Rbn\Framework\Core\Base\Data\BaseRepository;

/**
 * MasterLicenceRepository - Merkezi Lisans Veri Erisim Katmani 🔑🏛️
 *
 * TEK lisans tablosu (`licences`) uzerinden hem proje hem uygulama lisansini
 * yonetir. Donen dizi anahtarlari = sutun adlari (asla `SELECT *` yok).
 *
 * Servisler bu sozlesmeye gore yaziliyor; AD ve IMZALAR KESINLIKLE
 * degistirilmemelidir.
 *
 * @property \Rbn\Framework\Core\Database\Models\Master\MasterLicenceModel $masterLicenceModel
 */
class MasterLicenceRepository extends BaseRepository
{
    /** @var string Target primary model alias */
    protected $targetModel = 'master.licence';

    /** Lisans tablosunun sutun sirasi = donen dizi anahtarlari. */
    private const FIELDS = [
        'id', 'licence_key', 'subject_type', 'subject_id', 'tier', 'status',
        'expires_at', 'max_activations', 'device_hash', 'notes', 'created_at', 'updated_at',
    ];

    /** Yazilabilir sutunlar (id/zaman damgalari model tarafindan yonetilir). */
    private const WRITABLE = [
        'licence_key', 'subject_type', 'subject_id', 'tier', 'status',
        'expires_at', 'max_activations', 'device_hash', 'notes',
    ];

    /** @var string[] Lisans durumu beyaz listesi (ENUM ile ayni). */
    private const STATUSES = ['active', 'suspended', 'revoked', 'expired'];

    /** Anahtarla lisans arar (bulunamazsa null). */
    public function findByKey(string $key): ?array
    {
        if ($key === '') {
            return null;
        }

        $row = $this->model('master.licence')
            ->select(implode(', ', self::FIELDS))
            ->where('licence_key', $key)
            ->first();

        return $this->satir($row);
    }

    /** Sahibe (projeye veya uygulamaya) ait lisansi arar (bulunamazsa null). */
    public function findForSubject(string $type, int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = $this->model('master.licence')
            ->select(implode(', ', self::FIELDS))
            ->where('subject_type', $type)
            ->where('subject_id', $id)
            ->first();

        return $this->satir($row);
    }

    /** Yeni lisans yazar ve kayit id'sini dondurur. */
    public function create(array $data): int
    {
        $satir = $this->yazilabilir($data);

        // FW-BASE-2 (T4): `status` korumali alan; yalnizca bu yazici deger
        // beyaz listeli sabitten gelir (`MasterLicenceConfig`).
        return (int) $this->model('master.licence')
            ->authorizeFields(['status'])
            ->create($satir);
    }

    /** Lisans durumunu degistirir (active/suspended/revoked/expired). */
    public function updateStatus(int $id, string $status): bool
    {
        if ($id <= 0 || !in_array($status, self::STATUSES, true)) {
            return false;
        }

        // FW-BASE-2 (T4): durum beyaz listeli bir SABITTEN gelir, istemden
        // degil; bu yuzden yazma yetkili yoldan gecer ve her cagri loglanir.
        return (bool) $this->model('master.licence')
            ->authorizeFields(['status'])
            ->update($id, ['status' => $status]);
    }

    /** Lisansi tek bir cihaza baglar (device_hash). */
    public function bindDevice(int $id, string $deviceHash): bool
    {
        if ($id <= 0 || trim($deviceHash) === '') {
            return false;
        }

        return (bool) $this->model('master.licence')->update($id, ['device_hash' => $deviceHash]);
    }

    /** Lisans anahtari benzersiz mi? */
    public function isKeyUnique(string $key): bool
    {
        if ($key === '') {
            return false;
        }

        return $this->model('master.licence')->where('licence_key', $key)->first() === null;
    }

    /** Lisans listesi (en yeni once, sayfali). */
    public function list(int $limit = 100, int $offset = 0): array
    {
        $rows = $this->model('master.licence')
            ->select(implode(', ', self::FIELDS))
            ->orderBy('id', 'DESC')
            ->limit(max(1, $limit))
            ->offset(max(0, $offset))
            ->get()
            ->all();

        $liste = [];
        foreach ($rows as $row) {
            $satir = $this->satir($row);
            if ($satir !== null) {
                $liste[] = $satir;
            }
        }

        return $liste;
    }

    /** Hydrate edilen model nesnesini yalnizca sutun anahtarli diziye cevirir. */
    protected function satir(mixed $row): ?array
    {
        if (!is_object($row) && !is_array($row)) {
            return null;
        }

        $satir = [];
        foreach (self::FIELDS as $alan) {
            // Model ArrayAccess uygular; dizi de ayni sozlesmeyi verir.
            $satir[$alan] = $row[$alan] ?? null;
        }

        return $satir;
    }

    /** Yazma girdisini beyaz listeye gore temizler (bilinmeyen sutun eklenmez). */
    protected function yazilabilir(array $data): array
    {
        $satir = [];
        foreach (self::WRITABLE as $alan) {
            if (array_key_exists($alan, $data)) {
                $satir[$alan] = $data[$alan];
            }
        }

        return $satir;
    }
}
