<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Bridges\Helpers\Library;

use Rbn\Framework\Core\System\Paths\Paths;

/**
 * LogThrottle - TANILAMA GUNLUKLERI ICIN SAATLIK KAPI. 🚦
 *
 * YER: mevcut yardımcı sınıfların yanında (`Core/Support/Bridges/Helpers/
 * Library/`; `DataHelper`, `PathHelper`, `DebugHelper` ile aynı katman).
 * DİKKAT: bu klasör `Core/System/Storage/` DEĞİLDİR; `fw_env_kayit.php`
 * kalıntı taraması `Storage` dizinlerini kasten dışarıda bırakır, yani
 * `Storage` altına konan bir sınıf "ortam anahtarı kanıtı" taramasında
 * GÖRÜLMEZ ve `FW-ENV-KAYIT` kuralı kırılırdı.
 *
 * SORUN (FW-LOG-GURULTU, olcum): "log-only" amaçlı görünürlük satırları
 * **her istekte** yazılıyordu. Ölçülen gerçek: 162 isteklik duman koşusunda
 * B-92 uyarısı 596 satır, `MODEL_NOT_SCOPED` 2098 satır üretti; PHP-FPM'de
 * "süreç başına bir kez" koruması **işe yaramaz** (her istek yeni
 * süreç/çalıştırma). Sonuç: günlük/disk şişmesi ve gerçek hataların gürültüde
 * kaybolması.
 *
 * BU SINIF NE YAPAR: Aynı anahtar için TTL içinde **yalnız ilk çağrıya**
 * `true` döner. Tanılama satırları bu kapının arkasına girer → görünürlük
 * KALIR (saatte bir), gürültü KAYBOLUR.
 *
 * DEPOLAMA (sırayla):
 *   1. APCu varsa `apcu_add()` (atomik "ekle veya yok").
 *   2. Yoksa `Storage/cache/log-throttle/<sha256>.throttle` dosyası (`flock`).
 *      Bu dizin `.gitignore`'dadır (proje `Storage/` ağacı); sayaç depoya sızmaz.
 *   3. İkisi de kullanılamazsa **FAIL-CLOSED**: `false` döner, HİÇBİR log
 *      yazılmaz. Amaç: istek ASLA bozulmasın ve günlük şişmesin.
 *
 * FAIL-CLOSED DAVRANIŞ SÖZLEŞMESİ (metot imzası):
 *   - `true`  = "bu satırı yaz" (TTL içinde ilk çağrı).
 *   - `false` = "bu satırı YAZMA" (TTL dolu **veya** depolama kullanılamıyor).
 *   - **HİÇBİR istisna fırlatmaz.** Çağıran kodun `try/catch` yazmasına
 *     gerek kalmaz; ölçüm katmanı kararı bozamaz.
 *
 * ANAHTAR İÇERİĞİ: yalnız kalem etiketi + sınıf/öğe adı. Değer, PII, proje
 * anahtarı veya istek verisi ASLA anahtara girmez (Anayasa §9).
 *
 * ORTAM KAPISI: `RBN_LOG_THROTTLE=0|false|off|no|hayir` → kapi AÇILIR, her
 * çağrı `true` döner (yalnız TEST/ölçüm için). Varsayılan: kapı AÇIK.
 * Değişken `EnvKeys::RBN_LOG_THROTTLE` olarak kayıtlıdır ve **tek kapıdan**
 * (`Env::flag()`) okunur (FW-ENV-KAYIT kuralı).
 *
 * @see \Rbn\Framework\Core\System\Config\Env
 * @see \Rbn\Framework\Core\System\Config\Definitions\EnvKeys
 */
final class LogThrottle
{
    /**
     * Depolama alt dizini (proje `Storage/cache` altında).
     * `FolderMatrix` yalnız `Storage/cache` kökünü listeler; alt dizin
     * çalışma anında `mkdir` ile oluşturulur.
     */
    public const STORAGE_SUBDIR = 'cache/log-throttle';

    /** Varsayılan TTL: saatte bir (brif kararı). */
    public const DEFAULT_TTL = 3600;

    /**
     * Süreç içi hızlı yol: anahtar → TTL bitiş anı.
     *
     * PHP-FPM'de her istek yeni süreç olduğu için bu dizi tek işe yarar;
     * yine de APCu/dosya açmadan önce aynı süreçteki tekrarı keser.
     *
     * @var array<string,int>
     */
    private static array $local = [];

    /**
     * Süreç içi dizin hafızası: `['dir' => string|null]`.
     *
     * @var array<string,string|null>
     */
    private static array $dirCache = [];

    /**
     * Testler için zaman sahteleme (saniye). `null` = gerçek `time()`.
     *
     * @var int|null
     */
    private static ?int $clockOverride = null;

    /**
     * ANA API: aynı anahtar için TTL içinde yalnız ilk çağrıda `true`.
     *
     * @param string $key        Kalem etiketi + sınıf/öğe adı (değer/PII YOK).
     * @param int    $ttlSeconds Süre (saniye). `<= 0` verilirse `self::DEFAULT_TTL`.
     *
     * @return bool `true` = bu çağrıda log YAZ; `false` = YAZMA (ya da depolama yok).
     */
    public static function once(string $key, int $ttlSeconds = self::DEFAULT_TTL): bool
    {
        // KAPI AÇIKSA (test/ölçüm): sürekli yaz.
        if (!self::enabled()) {
            return true;
        }

        $ttl = $ttlSeconds > 0 ? $ttlSeconds : self::DEFAULT_TTL;
        $now = self::now();
        $hash = self::normalizeKey($key);

        // 0) Süreç içi hızlı yol (dosya/APCu açmadan).
        if (isset(self::$local[$hash]) && self::$local[$hash] > $now) {
            return false;
        }

        try {
            $aldik = self::claim($hash, $now, $ttl);
        } catch (\Throwable) {
            // Depolama katmanı patladıysa da istek BOZULMAZ; gürültü de
            // yazılmaz (fail-closed).
            return false;
        }

        if ($aldik === null) {
            return false;   // depolama kullanılamıyor → yazma
        }

        if ($aldik === false) {
            // Başka bir çağrı slotu ALDI; biz yazmayız ama süreç içi hızlı
            // yolun çalışması için bitişi hatırla.
            self::$local[$hash] = $now + $ttl;

            return false;
        }

        self::$local[$hash] = $now + $ttl;

        return true;
    }

    /**
     * Anahtarı depolamaya YAZAR.
     *
     * SIRALAMA ÖNEMLİ (ölçümle bulundu): **DOSYA birincil kaynaktır, APCu
     * yalnızca süreç içi hızlı yoldur.** APCu, PHP-FPM'de *çalışan süreç*
     * belleğindedir; her FPM worker'ın kendi kopyası vardır. APCu tek başına
     * kullanılırsa aynı proje, aynı saat içinde N worker'a dağıldıkça N kez
     * "ilk çağrı" sanılır (ölçüm: ikinci duman koşusunda APCu tek başına
     * 13 satır sızdı). Dosya `flock` ile TÜM süreçler arasında paylaşıldığı
     * için saatlik kapı gerçekten tutuyor.
     *
     * @return bool|null `true` = bu çağrı slotu ALDI (yaz);
     *                    `false` = slot zaten dolu (yazma);
     *                    `null` = HİÇBİR depolama kullanılamıyor (fail-closed).
     */
    private static function claim(string $hash, int $now, int $ttl): ?bool
    {
        $dizin = self::directory();
        if ($dizin !== null) {
            $sonuc = self::claimOnDisk($dizin, $hash, $now, $ttl);
            if ($sonuc !== null) {
                // Dosya kararı kesin; APCu'yu yalnızca aynı süreçteki
                // tekrarları ucuzlatmak için doldur.
                self::apcuRemember($hash, $now + $ttl);

                return $sonuc;
            }
        }

        // Dosya yok/yazılamıyor: APCu'ya düş (yoksa fail-closed).
        if (self::apcuAvailable()) {
            return self::claimApcu($hash, $now, $ttl);
        }

        return null;
    }

    /**
     * Yedek depolama: APCu. Yalnız dosya kullanılamadığında devreye girer.
     *
     * `apcu_add` atomiktir ("yoksa ekle"). Çakışmada değer okunur; süresi
     * dolmuşsa **kısa bir yarış penceresi** kabul edilir: bu bir log kısıtıdır,
     * güvenlik kararı DEĞİLDİR.
     */
    private static function claimApcu(string $hash, int $now, int $ttl): ?bool
    {
        $bitis = $now + $ttl;
        if (@apcu_add(self::apcuKey($hash), $bitis, $ttl) === true) {
            return true;
        }

        $mevcut = @apcu_fetch(self::apcuKey($hash), $basarili);
        if ($basarili === true && is_int($mevcut) && $mevcut > $now) {
            return false;   // TTL dolu → yazma
        }

        // Süresi dolmuş ya da değer bozuk: yeniden yaz ve geç.
        @apcu_store(self::apcuKey($hash), $bitis, $ttl);

        return true;
    }

    /** Dosya kararından sonra APCu aynasını tazeler (başarısızsa sessizce geç). */
    private static function apcuRemember(string $hash, int $bitis): void
    {
        if (!self::apcuAvailable()) {
            return;
        }
        $kalan = $bitis - time();
        if ($kalan > 0) {
            @apcu_store(self::apcuKey($hash), $bitis, $kalan);
        }
    }

    /**
     * Dosya yolu (BİRİNCİL depolama):
     * `Storage/cache/log-throttle/<sha256>.throttle`, `flock(LOCK_EX)` ile atomik.
     *
     * @return bool|null `LogThrottle::claim()` sözleşmesi.
     */
    private static function claimOnDisk(string $dizin, string $hash, int $now, int $ttl): ?bool
    {
        $dosya = $dizin . DIRECTORY_SEPARATOR . $hash . '.throttle';

        // 'c+' = varsa KIRPMA, yoksa OLUŞTUR (içeriği biz okuyacağız).
        $fh = @fopen($dosya, 'c+b');
        if ($fh === false) {
            return null;   // yazılamıyor → fail-closed
        }

        try {
            if (@flock($fh, LOCK_EX) === false) {
                return null;
            }

            $ham = stream_get_contents($fh);
            $mevcut = is_string($ham) ? (int) trim($ham) : 0;
            if ($mevcut > $now) {
                return false;   // TTL dolu → yazma
            }

            if (@ftruncate($fh, 0) === false) {
                return null;
            }
            rewind($fh);
            if (@fwrite($fh, (string) ($now + $ttl)) === false) {
                return null;
            }
            @fflush($fh);

            return true;
        } finally {
            @flock($fh, LOCK_UN);
            @fclose($fh);
        }
    }

    /**
     * Throttle dosyalarının dizini; kurulabiliyorsa döner, yoksa `null`.
     *
     * `Paths` hazır değilse (çok erken boot) veya dizin yazılamıyorsa `null`.
     *
     * SÜREÇ İÇİ HAFIZA: `is_dir`/`mkdir`/`is_writable` her log satırında üç
     * sistem çağrısı demek; sonuç bir istek içinde değişmediği için bir kez
     * çözülüp saklanır (`null` de saklanır: "bu süreçte kullanılamaz").
     */
    private static function directory(): ?string
    {
        if (array_key_exists('dir', self::$dirCache)) {
            return self::$dirCache['dir'];
        }

        self::$dirCache['dir'] = self::resolveDirectory();

        return self::$dirCache['dir'];
    }

    /** Dizin çözümlemesi (önbellek uygulanmaz). */
    private static function resolveDirectory(): ?string
    {
        try {
            if (!Paths::isInitialized()) {
                return null;
            }
            $dizin = rtrim((string) Paths::project()->storage(self::STORAGE_SUBDIR), '/\\');
        } catch (\Throwable) {
            return null;
        }

        if ($dizin === '') {
            return null;
        }

        if (!is_dir($dizin) && !@mkdir($dizin, 0775, true) && !is_dir($dizin)) {
            return null;
        }

        return is_writable($dizin) ? $dizin : null;
    }

    /** APCu gerçekten kullanılabilir mi? (fonksiyon + etkin bayrak) */
    private static function apcuAvailable(): bool
    {
        return function_exists('apcu_add')
            && (bool) @apcu_enabled();
    }

    /** APCu anahtarı: namespace'lenmiş, kısaltılmış. */
    private static function apcuKey(string $hash): string
    {
        return 'rbn.logthrottle.' . $hash;
    }

    /**
     * Anahtarı güvenli dosya/anahtar adına indirger.
     *
     * Kullanıcı girdisi anahtara giremez (hash); yol taşıma ve dizin
     * oluşturma vektörü kapatılır. Boş anahtar sabit bir kova adına düşer.
     */
    private static function normalizeKey(string $key): string
    {
        $key = trim($key);
        if ($key === '') {
            return hash('sha256', 'rbn.logthrottle.anon');
        }

        return hash('sha256', 'rbn.logthrottle.' . $key);
    }

    /**
     * Kapi açık mı? (yalnız `Env` okur; `RBN_LOG_THROTTLE=0` kapatır)
     *
     * Ad `EnvKeys::RBN_LOG_THROTTLE` sabitinde kayıtlıdır; diğer okuyucularla
     * aynı desenle (`Env::flag('AD', ...)`) okunur.
     */
    private static function enabled(): bool
    {
        try {
            return \Rbn\Framework\Core\System\Config\Env::flag('RBN_LOG_THROTTLE', true);
        } catch (\Throwable) {
            // `Env` kayıt dışı adı fail-closed reddedebilir; bu durumda
            // throttle AÇIK kalır (gürültü öncelikli değil, görünürlük önemli:
            // en kötü halde eski davranış).
            return true;
        }
    }

    /** Şimdiki zaman (testte sahte olabilir). */
    private static function now(): int
    {
        return self::$clockOverride ?? time();
    }

    /**
     * YALNIZ TEST: zaman kaynağını sahteleştirir (`null` = gerçek zaman).
     *
     * Üretimde çağrılmaz; birim testler TTL sonrası davranışını beklemeden
     * doğrular.
     */
    public static function setClockOverride(?int $timestamp): void
    {
        self::$clockOverride = $timestamp;
    }

    /**
     * YALNIZ TEST: süreç içi önbelleği (ve istenirse diskteki dosyaları) siler.
     *
     * @param string|null $key `null` = süreç içi önbelleğin TAMAMI; verilirse
     *                         yalnız o anahtarın dosyası silinir.
     */
    public static function reset(?string $key = null): void
    {
        self::$clockOverride = null;
        self::$dirCache = [];

        if ($key === null) {
            self::$local = [];

            return;
        }

        $hash = self::normalizeKey($key);
        unset(self::$local[$hash]);

        $dizin = self::directory();
        if ($dizin !== null) {
            @unlink($dizin . DIRECTORY_SEPARATOR . $hash . '.throttle');
        }
    }
}
