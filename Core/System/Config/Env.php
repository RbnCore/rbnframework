<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Config;

use Rbn\Framework\Core\System\Config\Definitions\EnvKeys;

require_once __DIR__ . '/Definitions/EnvKeys.php';

/**
 * Env - ORTAM DEĞİŞKENİ OKUYUCUSU (TEK KAPI). 🔐
 *
 * FW-ENV-KAYIT-160 (Baran): `getenv(` / `$_ENV` / `$_SERVER` ile bir ortam
 * değişkeni okuyan HER yer bu sınıfa taşınır. Amacı: "global anahtarlar TEK
 * dosyada toplansın; herkes her yerde ayrı okuyup tanımlamasın."
 *
 * OKUMA SIRA SIRASI (aşağıdaki sıranın KENDİSİ bu sınıfın kuralıdır):
 *   1. `$_ENV[$ad]`
 *   2. `$_SERVER[$ad]`
 *   3. `getenv($ad)`            (CGI/FastCGI'de `$_ENV` doldurulmaz)
 * Her kaynakta BOŞ (`''`) veya metin olmayan değer **geçilir**; ilk dolu
 * metin döner, hiçbiri dolu değilse `null`.
 *
 * TEMBELLİK (LAZY): değerler ilk okunduğunda bir kez hesaplanıp
 * `self::$cache` içinde saklanır. Ortam bir istek içinde değişmez; böylece
 * `getenv()` her çağrıldığında yapılmaz. Testler için `Env::reset()` vardır.
 *
 * KAYITSIZ AD = FAIL-CLOSED: `EnvKeys::ALL_KEYS` listesinde olmayan bir ad
 * okunmaya çalışılırsa `RuntimeException` fırlatılır. Böylece yazım hatası
 * sessizce "ortam değişkeni yok" sayılmaz; kurulumda fark edilir. Tabloya
 * eklemek `Core/System/Config/README.md` içindeki tek satırlık kuraldır.
 *
 * GİZLİ ADLAR: `EnvKeys::SECRET_KEYS` listesinde olan adların **değeri**
 * ne hata mesajına ne loga ne de istisna metnine yazılır. Hata mesajı yalnız
 * ADI + nedeni + "değer gösterilmiyor" bilgisini taşır.
 *
 * @see \Rbn\Framework\Core\System\Config\Definitions\EnvKeys
 * @see \Rbn\Framework\Core\System\Config\Secrets
 */
final class Env
{
    /**
     * ad => string|null (tembel önbellek; `null` = tanımsız).
     *
     * @var array<string, string|null>|null
     */
    private static ?array $cache = null;

    /**
     * Metin okuyucu. Kayıtlı değilse fail-closed hata fırlatır.
     *
     * @param string      $name        Kayıtlı ortam değişkeni adı.
     * @param string|null $defaultValue Ortam tanımsız/boş ise döndürülecek değer.
     *
     * @throws \RuntimeException Ad `EnvKeys::ALL_KEYS` listesinde kayıtlı değilse.
     */
    public static function string(string $name, ?string $defaultValue = null): ?string
    {
        self::requireRegistered($name);

        return self::fromCache($name) ?? $defaultValue;
    }

    /**
     * Bayrak (kill-switch) okuyucu.
     *
     * AÇIK/KAPALI YORUMU TEK LİSTEDEN: `ShieldSettingsRepository::normalizeSwitch()`
     * - `0 | false | off | no | hayir` KAPALI, diğer her şey AÇIK (fail-closed:
     * belirsizlik güvenli tarafa düşer). **İkinci bir parser YAZILMAZ.**
     * Autoloader henüz yüklenmemişse (PreBoot erken aşaması) aynı liste
     * aşağıda elle kullanılır; birim testi `fw_env_kayit.php` iki listeyin
     * birebir aynı olduğunu zorunlu kılar.
     *
     * @param bool $defaultValue Ortam tanımsız/boş olduğunda karar (kill-switch'lerde `true` = fail-closed).
     *
     * @throws \RuntimeException Ad `EnvKeys::ALL_KEYS` listesinde kayıtlı değilse.
     */
    public static function flag(string $name, bool $defaultValue = true): bool
    {
        self::requireRegistered($name);
        $value = self::fromCache($name);
        if ($value === null) {
            return $defaultValue;
        }

        return self::normalizeSwitch($value, $defaultValue);
    }

    /**
     * Tam sayı okuyucu. Sayı OLMAYAN metin `null` döner (sessizce 0'a
     * çevrilmez - 0 "geçersiz" ile "sıfır" arasındaki fark kaybolur).
     *
     * @param string    $name         Kayıtlı ortam değişkeni adı.
     * @param int|null  $defaultValue Ortam tanımsız/boş/geçersizse dönecek değer.
     *
     * @throws \RuntimeException Ad `EnvKeys::ALL_KEYS` listesinde kayıtlı değilse.
     */
    public static function int(string $name, ?int $defaultValue = null): ?int
    {
        self::requireRegistered($name);
        $value = self::fromCache($name);
        if ($value === null) {
            return $defaultValue;
        }

        $clean = trim($value);
        if ($clean === '' || preg_match('/^-?\d+$/', $clean) !== 1) {
            return $defaultValue;
        }

        return (int) $clean;
    }

    /**
     * Önbelleği boşaltır (yalnız TEST için; üretimde çağırma).
     */
    public static function reset(): void
    {
        self::$cache = [];
    }

    /**
     * Geriye uyum takma adı: eski Türkçe ad (`temizle`) yerine `reset()` kullan.
     * FW-ENV-KAYIT-160 ad çevirisi sırasında eklendi; birim testleri
     * (`fw_env_kayit.php`) hâlâ bu adı çağırıyor olabilir.
     *
     * @deprecated `reset()` kullan.
     */
    public static function temizle(): void
    {
        self::reset();
    }

    /**
     * Adı kayıt listesinde zorunlu kılar (fail-closed).
     *
     * Gizli adların DEĞERİ hiçbir koşulda istisna metnine girmez; burada
     * yalnız AD görünür (ad gizli değildir, değeri gizlidir).
     *
     * @throws \RuntimeException Ad `EnvKeys::ALL_KEYS` listesinde kayıtlı değilse.
     */
    private static function requireRegistered(string $name): void
    {
        if (in_array($name, EnvKeys::ALL_KEYS, true)) {
            return;
        }

        throw new \RuntimeException(
            'RBN Yapılandırma: "' . $name . '" ortam değişkeni Core/System/Config/Definitions/EnvKeys.php '
            . 'kayıt listesinde KAYITLI DEĞİL. Env yalnız kayıtlı adları okur (fail-closed); '
            . 'yeni bir anahtar ekliyorsan önce EnvKeys::ALL_KEYS içine bir sabit ekle '
            . '(bkz. Core/System/Config/README.md). Bu bir yazım hatası olabilir.'
        );
    }

    /**
     * Ad GİZLİ mi? (`EnvKeys::SECRET_KEYS` listesinde mi?)
     *
     * Kayıt dışı ad için `false` döner - bilgi sızdırmamak için. Değeri ASLA
     * döndürmez; yalnız "bu adın değeri mesajlara yazılmaz" bilgisini taşır.
     */
    public static function isSecretKey(string $name): bool
    {
        return in_array($name, EnvKeys::SECRET_KEYS, true);
    }

    /** Önbellekten okur; yoksa kaynaklardan çözüp YERLEŞTİRİR. */
    private static function fromCache(string $name): ?string
    {
        if (self::$cache === null) {
            self::$cache = [];
        }
        if (!array_key_exists($name, self::$cache)) {
            self::$cache[$name] = self::resolveFromSources($name);
        }

        return self::$cache[$name];
    }

    /**
     * `$_ENV` -> `$_SERVER` -> `getenv` sırasıyla ÇÖZER; metin olmayan ve boş
     * değerler geçilir. Bu sıranın KENDİSİ tek kuraldır (aşağıda yorum var).
     */
    private static function resolveFromSources(string $name): ?string
    {
        // 1) $_ENV - doğrudan tanımlanmış ortam (CLI'de dolu).
        $v = $_ENV[$name] ?? null;
        $text = self::toText($v);
        if ($text !== null) {
            return $text;
        }

        // 2) $_SERVER - CGI/FastCGI'de `$_ENV` DOLDURULMAZ, burası dolar.
        $text = self::toText($_SERVER[$name] ?? null);
        if ($text !== null) {
            return $text;
        }

        // 3) getenv - son çare (disable_functions ile kapalı olabilir).
        if (!function_exists('getenv')) {
            return null;
        }

        return self::toText(@getenv($name));
    }

    /**
     * Değeri `null`-dönen metne çevirir: metin DEĞİLSE veya BOSSA `null`
     * (kaynak sıralamasında bir sonrakine geçilsin diye).
     */
    private static function toText(mixed $v): ?string
    {
        if (!is_string($v)) {
            return null;
        }
        $v = trim($v);

        return $v === '' ? null : $v;
    }

    /**
     * A0-3 TEK MERKEZİ: `ShieldSettingsRepository::normalizeSwitch()` ile
     * AYNI. Autoloader yoksa aynı liste elle kullanılır.
     *
     * DİKKAT: burada ikinci bir yorum listesi YAZILMAZ. Liste
     * `EnvKeys::OFF_VALUES` sabitinde yaşar (sabitler ayrı dosyada) ve
     * `ShieldSettingsRepository::KAPALI_DEGERLER` ile birebir aynı olmak
     * ZORUNDADIR (birim testi `fw_env_kayit.php` / C kümesi bunu doğrular).
     */
    private static function normalizeSwitch(string $raw, bool $defaultValue): bool
    {
        $repo = 'Rbn\\Framework\\Core\\Database\\Repositories\\Common\\ShieldSettingsRepository';
        if (class_exists($repo)) {
            return $repo::normalizeSwitch($raw, $defaultValue);
        }

        // Autoloader henüz yüklenmemiş (PreBoot erken aşaması): AYNI liste.
        $v = strtolower(trim($raw));
        if ($v === '') {
            return $defaultValue;
        }

        return !in_array($v, EnvKeys::OFF_VALUES, true);
    }
}