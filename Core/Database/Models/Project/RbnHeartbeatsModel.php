<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Models\Project;

use Rbn\Framework\Core\Base\Data\BaseModel;
use Rbn\Framework\Core\Support\Contracts\Base\BaseModelInterface;

/**
 * RbnHeartbeatsModel - Project Telemetry Hub 💓🛰️
 *
 * [G-09] GUVENLI DUSUS — bu model ASLA istegi 500'e dusurmez.
 *
 * SORUN: `z_sys_heartbeats` tablosu her projede kurulu DEGIL. Tabela
 * yazilamayan projelerde bir heartbeat yazma denemesi `PDOException`
 * ("Table doesn't exist") firlatir ve HTTP istegi 500 olur — telemetri
 * (izleme) hicbir zaman istegi dusurmemesi gereken bir konudur.
 *
 * COZUM (sema degisikligi YOK):
 *   - Tablo adi kiraci (tenant) baglamindan COZULUR; sabit yazilmaz.
 *   - Tablo yoksa: bir kez gunluge yazilir, sonra TUM veri yollari
 *     guvenli varsayilana doner (`false` / `0` / `null` / `[]`) ve akis
 *     DEVAM eder.
 *   - Hicbir yerde CREATE TABLE / ALTER / migration YOK; sema yalniz
 *     okunur (bu siniftan DDL cagrilmaz).
 *
 * @property string $heartbeat_key
 * @property string $last_beat
 * @property string $payload
 *
 * @see \Rbn\Framework\Core\Base\Data\BaseModel
 */
class RbnHeartbeatsModel extends BaseModel implements BaseModelInterface
{
    /** Varsayilan tablo adi; kiraci baglaminda cozulemezse bu kullanilir. */
    public const DEFAULT_TABLE = 'z_sys_heartbeats';

    /** Anahtar sutunu. */
    public const KEY_COLUMN = 'heartbeat_key';

    /**
     * Kiraci baglaminda cozulacak TABLO ADI ADAYLARI (sema degisikligi YOK).
     *
     * `ProjectDbData::REQUIRED_TABLES` icinde `z_sys_heartbeats` YOKTUR:
     * tablo her projede kurulu olmak zorunda degildir ve eski/aktarilmis
     * projelerde adi farkli olabilir. Bu yuzden ad TEK sabit olarak
     * varsayilmaz; kiracinin veritabaninda VAR OLAN ilk aday secilir.
     *
     * @var string[]
     */
    public const TABLE_CANDIDATES = [
        'z_sys_heartbeats',   // framework varsayilani
        'rbn_heartbeats',     // master modeliyle ayni ad
        'sys_heartbeats',     // oneksiz varyant
        'heartbeats',         // en minimal varyant
    ];

    protected string $connection = 'database_project';
    protected $table = self::DEFAULT_TABLE;
    /**
     * FW-ALTYAPI-2 H / G1 - kiraci izolasyonu BEYANI (beyan zorunlulugu).
     *
     * Kapsam  : kimlik/oturum tablosu.
     * Gerekce: kimlik tablosu: `project_key` kapsam alani degil, ayri veritabani zaten kiraci ayrimi yapar; kolon bilincli EKLENMEZ.
     *
     * Varsayilan `BaseModel::$scoped` DEGISTIRILMEDI: kiraci izolasyonu
     * varsayilan olarak KAPALI kalir (acmak girisi 7 veritabaninda
     * kirar - FW-ALTYAPI-1 H 4.2 olculdu). G1 yalniz BEYAN ZORUNLULUGU
     * getirir: her model kararini kendi dosyasinda yazar.
     *
     * @tenant-scope identity
     */
    protected bool $scoped = false;
    protected $primaryKey = 'heartbeat_key';
    public bool $incrementing = false;
    protected string $keyType = 'string';
    protected bool $timestamps = false;

    /**
     * Tablo varliginin su an incelenip incelenmedigi (surec basina onbellek).
     * @var array<string, bool>
     */
    private static array $tabloOnbellegi = [];

    /**
     * Cozulmus tablo adi (surec basina TEK; kiraci baglami istekler
     * arasinda degismez).
     * @var string|null
     */
    private static ?string $cozulmusTablo = null;

    /**
     * [G-09] Taban sinif kurulduktan SONRA tablo adi kiraci baglamindan
     * cozulur. `BaseModel::__construct()` kesfi tamamlar; burada yalniz
     * tabya adini guncelleriz.
     */
    public function __construct()
    {
        parent::__construct();

        if (self::$cozulmusTablo === null) {
            self::$cozulmusTablo = $this->resolveTableName();
        }
        $this->table = self::$cozulmusTablo;
    }

    /** @var bool Ayni istek icinde "tablo yok" uyarisi yazildi mi? (gunluk gurultusunu onler) */
    private static bool $uyariYazildi = false;

    /* ==========================================================================
       [G-09] TABLO COZUMLEME + GUVENLI DUSUS
       ========================================================================== */

    /**
     * Kiraci baglamindan tablo adini COZER: adaylar sirayla denenir,
     * kiracinin veritabaninda VAR OLAN ilki secilir.
     *
     * Hicbiri yoksa (ya da baglanti acilamazsa) varsayilan ad doner —
     * `isAvailable()` zaten `false` donerek akisi kacisiyla devam ettirir.
     * Sema OLUSTURMA YOK.
     *
     * @return string Cozulmus tablo adi (yalniz ` harf/rakam/alt cizgi).
     */
    public function resolveTableName(): string
    {
        foreach (self::TABLE_CANDIDATES as $aday) {
            $guvenli = $this->guvenliTabloAdi((string) $aday);
            try {
                if ($this->probeTable($guvenli)) {
                    return $guvenli;
                }
            } catch (\Throwable $e) {
                // Baglanti sorunu: sonraki adaya gecme, sonunda guvenli dusus.
                return $guvenli;
            }
        }
        return self::DEFAULT_TABLE;
    }

    /**
     * Tablo adi guvenli karakter kumesine indirgenir (SQL enjeksiyonu
     * yuzeyi KALDIRILIR; yalniz ` harf/rakam/alt cizgi).
     */
    private function guvenliTabloAdi(string $ad): string
    {
        $temiz = preg_replace('/[^A-Za-z0-9_]/', '', $ad);
        return ($temiz === null || $temiz === '') ? self::DEFAULT_TABLE : $temiz;
    }

    /**
     * Aktif kiracinin veritabaninda heartbeat tablosu VAR MI?
     *
     * Sonuc istek basina bir kez onbelleklenir; `SHOW TABLES`/`information_schema`
     * her istekte calismaz.
     */
    public function isAvailable(): bool
    {
        $ad = $this->guvenliTabloAdi((string) $this->table);
        $anahtar = $this->connection . '|' . $ad;

        if (isset(self::$tabloOnbellegi[$anahtar])) {
            return self::$tabloOnbellegi[$anahtar];
        }

        try {
            self::$tabloOnbellegi[$anahtar] = $this->probeTable($ad);
        } catch (\Throwable $e) {
            // Baglanti hic acilamadiysa da 500 DUSMEYIZ: telemetri opsiyoneldir.
            self::$tabloOnbellegi[$anahtar] = false;
        }

        return self::$tabloOnbellegi[$anahtar];
    }

    /**
     * Gercek varlik denetimi (yalniz SEMA OKUNUR; hicbir DDL yok).
     * Test alt sinifi tarafindan ezilebilir.
     */
    protected function probeTable(string $tablo): bool
    {
        return (bool) $this->db
            ->schema($tablo)
            ->connection($this->connection)
            ->exists();
    }

    /**
     * Tablo yoksa bir kez gunluge yazar (surec basina TEK).
     */
    private function warnMissingTable(string $ad): void
    {
        if (self::$uyariYazildi) {
            return;
        }
        self::$uyariYazildi = true;

        try {
            if (method_exists($this, 'logs')) {
                $this->logs()->channel('database')->warning(
                    'Heartbeat tablosu bulunamadi, telemetri atlandi: ' . $ad
                    . ' (proje=' . $this->getActiveProjectKey() . ')'
                );
            }
        } catch (\Throwable $e) {
            // Gunluk yoksa da akis devam eder.
        }
    }

    /** Onbellegi temizler (uzun sureli CLI surecleri / birim testleri icin). */
    public static function onbellegiTemizle(): void
    {
        self::$tabloOnbellegi = [];
        self::$uyariYazildi = false;
    }

    /* ==========================================================================
       [G-09] GUVENLI YAZMA API'SI
       ========================================================================== */

    /**
     * Telemetri nabzi yazar (upsert). Tablo yoksa `false` doner, HATA firlatmaz.
     *
     * @param string $anahtar   Kalp atisi anahtari (orn. 'cron/temizlik')
     * @param array  $payload   Serbest JSON yuku
     */
    public function beat(string $anahtar, array $payload = []): bool
    {
        if ($anahtar === '') {
            return false;
        }

        if (!$this->isAvailable()) {
            $this->warnMissingTable((string) $this->table);
            return false;
        }

        $simdi = date('Y-m-d H:i:s');
        try {
            $satir = [
                self::KEY_COLUMN => $anahtar,
                'last_beat'      => $simdi,
                'payload'        => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ];

            // Mevcut kayit mi? (upsert; yarim kalan durum birakilmaz)
            $mevcut = $this->query()->where(self::KEY_COLUMN, $anahtar)->first();

            if ($mevcut) {
                return $this->query()
                    ->where(self::KEY_COLUMN, $anahtar)
                    ->update(['last_beat' => $satir['last_beat'], 'payload' => $satir['payload']]);
            }

            return (bool) $this->query()->insert($satir);
        } catch (\Throwable $e) {
            // Yarim kalan hicbir sey olmaz: telemetri hicbir zaman istegi dusurmez.
            return false;
        }
    }

    /* ==========================================================================
       [G-09] TUM VERI YOLLARI GUVENLI DUSUS
       ========================================================================== */

    public function create(array $data): int|bool
    {
        if (!$this->guard()) {
            return false;
        }
        return parent::create($data);
    }

    public function update($id, array $data): bool
    {
        if (!$this->guard()) {
            return false;
        }
        return parent::update($id, $data);
    }

    public function delete(): bool
    {
        if (!$this->guard()) {
            return false;
        }
        return parent::delete();
    }

    public function destroy($id): bool
    {
        if (!$this->guard()) {
            return false;
        }
        return parent::destroy($id);
    }

    public function save(array $data): bool|int
    {
        if (!$this->guard()) {
            return false;
        }
        return parent::save($data);
    }

    public function insertBatch(array $data): bool
    {
        if (!$this->guard()) {
            return false;
        }
        return parent::insertBatch($data);
    }

    /** Telemetri: tablo yoksa `null` (hata degil). */
    public function find($id): ?array
    {
        if (!$this->guard()) {
            return null;
        }
        return parent::find($id);
    }

    /** Telemetri: tablo yoksa bos dizi (hata degil). */
    public function all(): array
    {
        if (!$this->guard()) {
            return [];
        }
        return parent::all();
    }

    /**
     * Ortak kapı: tablo yoksa uyar ve `false` don.
     */
    protected function guard(): bool
    {
        if ($this->isAvailable()) {
            return true;
        }
        $this->warnMissingTable((string) $this->table);
        return false;
    }
}