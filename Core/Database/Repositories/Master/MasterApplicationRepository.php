<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Repositories\Master;

use Rbn\Framework\Core\Base\Data\BaseRepository;

/**
 * MasterApplicationRepository - Uygulama Kaydi Veri Erisim Katmani 📦🏛️
 *
 * URUN surumu buradadir (`current_version` + `download_url` + `sha256`);
 * lisans tarafi `licences` tablosunda ve surum kolonu tasimaz.
 *
 * @property \Rbn\Framework\Core\Database\Models\Master\MasterApplicationModel $masterApplicationModel
 */
class MasterApplicationRepository extends BaseRepository
{
    /** @var string Target primary model alias */
    protected $targetModel = 'master.application';

    /** Uygulama tablosunun sutun sirasi = donen dizi anahtarlari. */
    private const FIELDS = [
        'id', 'app_key', 'name', 'platform', 'channel', 'status', 'current_version',
        'download_url', 'sha256', 'min_version', 'description', 'created_at', 'updated_at',
    ];

    /** Yazilabilir sutunlar (id/zaman damgalari model tarafindan yonetilir). */
    private const WRITABLE = [
        'app_key', 'name', 'platform', 'channel', 'status', 'current_version',
        'download_url', 'sha256', 'min_version', 'description',
    ];

    /** Uygulama anahtariyla kayit arar (bulunamazsa null). */
    public function findByAppKey(string $key): ?array
    {
        if ($key === '') {
            return null;
        }

        return $this->satir(
            $this->model('master.application')
                ->select(implode(', ', self::FIELDS))
                ->where('app_key', $key)
                ->first()
        );
    }

    /** Tum uygulama kayitlari (anahtara gore sirali). */
    public function list(): array
    {
        $rows = $this->model('master.application')
            ->select(implode(', ', self::FIELDS))
            ->orderBy('app_key', 'ASC')
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

    /** Yeni uygulama kaydi yazar ve kayit id'sini dondurur. */
    public function create(array $data): int
    {
        return (int) $this->model('master.application')->create($this->yazilabilir($data));
    }

    /** Uygulamanin yayindaki surum bilgisini gunceller. */
    public function updateVersion(int $id, string $version, ?string $url, ?string $sha256): bool
    {
        if ($id <= 0 || trim($version) === '') {
            return false;
        }

        $veri = ['current_version' => $version];
        if ($url !== null) {
            $veri['download_url'] = $url;
        }
        if ($sha256 !== null) {
            $veri['sha256'] = $sha256;
        }

        return (bool) $this->model('master.application')->update($id, $veri);
    }

    /** Hydrate edilen model nesnesini yalnizca sutun anahtarli diziye cevirir. */
    protected function satir(mixed $row): ?array
    {
        if (!is_object($row) && !is_array($row)) {
            return null;
        }

        $satir = [];
        foreach (self::FIELDS as $alan) {
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
