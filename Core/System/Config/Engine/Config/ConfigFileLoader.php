<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Config\Engine\Config;

/**
 * ConfigFileLoader - Yapilandirma / sir dosyalarinin ORTAK dosya teknigi.
 *
 * [FW-TEK-SECRETS-DOSYASI-161] Tek YAZIM (SSOT): "dosyayi bul -> izin kontrolu ->
 * icerik kontrolu -> `require` -> dizi dogrula -> anahtar dogrula -> tembel
 * onbellek" zinciri BIR kez burada yazildir.
 *
 * KURALLAR (ihlal edilirse hata firlatilir, sessiz devam YOK):
 *  1. Dosya yoksa / okunamazsa           -> RuntimeException (acik yol ipucu ile)
 *  2. Izin 0600'dan genis (Linux/macOS)  -> RuntimeException
 *  3. Icerik `<?php` ile baslamiyor       -> RuntimeException (sizinti)
 *  4. `return` ifadesi `?>` kapanisindan ONCE degilse -> RuntimeException (sizinti)
 *  5. Dosya dizi dondurmuyorsa           -> RuntimeException
 *  6. Zorunlu anahtar eksik / `CHANGE_ME` / bos -> RuntimeException
 *
 * NEDEN TRAIT: `Secrets.php` kernel/bootstrap asamasinda ve izole dizin
 * kopyalarinda **TEK BASINA** yuklenmek zorundadir; bu yuzden komsu dosyalari
 * autoloader yerine goreli `require` ile bulur. Ayni mantigin ikinci kez
 * yazilmasi SSOT ihlali olurdu.
 *
 * KAPSAM: bu trait yalniz yukleme/onbellek/anahtar dogrulamasindan sorumludur.
 * Izin + icerik + operator yolu korumasi `ConfigFileGuard` trait'indedir.
 *
 * LAZY: dosya YALNIZ ilk istendiginde okunur; bu yuzden `cpanel` bolumu
 * olmayan kurulumlar sif dosyasi olmadan acilir.
 *
 * @see \Rbn\Framework\Core\System\Config\Engine\Config\ConfigFileGuard
 * @see \Rbn\Framework\Core\System\Config\Secrets
 */
trait ConfigFileLoader
{
    use ConfigFileGuard;

    /**
     * Surec ici onbellek: `<dizin>\0<dosya>` => ham dizi.
     *
     * `self::` ile yazildigi icin trait'i kullanan HER sinif kendi onbelleğine
     * sahiptir.
     *
     * @var array<string,array<string,mixed>>
     */
    private static array $cache = [];

    /* ==========================================================================
       [ YUKLEME ]
       ========================================================================== */

    /**
     * Bir dosyayi **tembel** olarak yukler; sonucu onbellekler.
     *
     * @param string $dir        Dosyanin bulunacagi dizin.
     * @param string $file       Dosya adi (`secrets.php`).
     * @param string $displayPath Operatorun gorecegi tek dogru yol (hata mesajinda).
     * @return array<string,mixed> Dosyanin dondurdugu HAM dizi.
     * @throws \RuntimeException Dosya yoksa / okunamazsa / izin fazla genisse /
     *                          icerik sizdirirsa / dizi dondurmuyorsa.
     */
    public static function includeArray(string $dir, string $file, string $displayPath): array
    {
        $cacheKey = self::cacheKey($dir, $file);
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $path = rtrim($dir, '/\\') . '/' . ltrim($file, '/\\');

        if (!is_file($path) || !is_readable($path)) {
            throw new \RuntimeException(
                'RBN Guvenlik: ' . basename($file) . ' bulunamadi (beklenen konum: ' . $displayPath . '). '
                . self::hint($displayPath)
            );
        }

        self::guardFileMode($path, $displayPath, basename($file));
        self::guardFileContent($path, $displayPath, basename($file));

        /** @psalm-suppress UnresolvableInclude */
        $data = require $path;

        if (!is_array($data)) {
            throw new \RuntimeException(
                'RBN Guvenlik: ' . basename($file) . ' dizi dondurmuyor (beklenen konum: ' . $displayPath . '). '
                . 'Beklenen bicim: return [ \'anahtar\' => \'deger\' ];'
            );
        }

        /** @var array<string,mixed> $data */
        return self::$cache[$cacheKey] = $data;
    }

    /**
     * Duz (tek duzeyli) `anahtar => deger` diziye cevirir.
     *
     * Alt dizi/nesne degerler BOS STRING'e doner: sif deger degildir, bir
     * bolumun icerigidir; onlar `Secrets::section()`/`api()` ile okunur.
     *
     * @param array<string,mixed> $data
     * @return array<string,string>
     */
    public static function normalize(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            $out[(string) $key] = is_scalar($value) ? (string) $value : '';
        }

        return $out;
    }

    /**
     * Zorunlu anahtarlari tek tek dogrular (anahtar BASINA ayri dogrulama:
     * SMTP parolasi eksikse master DB parolasi okunmaya devam eder).
     *
     * `CHANGE_ME` (sablon) degeri HICBIR dosyada kabul edilmez.
     *
     * @param array<string,string> $flat
     * @param string[]              $required Zorunlu anahtarlar.
     * @param string[]              $nonEmpty Bos deger KABUL EDILMEYEN anahtarlar.
     * @param string                $label    Hata mesajinda gorunecek dosya/bolum adi.
     * @param string                $displayPath Operatorun bakacagi yol.
     * @throws \RuntimeException
     */
    public static function validate(
        array $flat,
        array $required,
        array $nonEmpty,
        string $label,
        string $displayPath
    ): void {
        $hint = self::hint($displayPath);

        foreach ($required as $key) {
            if (!array_key_exists($key, $flat)) {
                throw new \RuntimeException(
                    'RBN Guvenlik: ' . $label . ': ' . $key . ' tanimli degil. ' . $hint
                );
            }
            if ($flat[$key] === 'CHANGE_ME') {
                throw new \RuntimeException(
                    'RBN Guvenlik: ' . $label . ': ' . $key . ' sablon degerinde (CHANGE_ME). '
                    . 'Sablon (`CHANGE_ME`) degeri kabul edilmez. ' . $hint
                );
            }
            if (in_array($key, $nonEmpty, true) && trim($flat[$key]) === '') {
                throw new \RuntimeException(
                    'RBN Guvenlik: ' . $label . ': ' . $key . ' bos. ' . $hint
                );
            }
        }
    }

    /**
     * Duz dizi + anahtar dogrulama yapan kisa yol (tek-duzeyli dosyalar).
     *
     * @param string[] $required
     * @param string[] $nonEmpty
     * @return array<string,string>
     * @throws \RuntimeException
     */
    public static function load(
        string $dir,
        string $file,
        string $displayPath,
        array $required = [],
        array $nonEmpty = []
    ): array {
        $flat = self::normalize(self::includeArray($dir, $file, $displayPath));
        self::validate($flat, $required, $nonEmpty, basename($file), $displayPath);

        return $flat;
    }

    /** Onbellek anahtari: dizin + dosya (sahte dizin testleri icin ayirt edici). */
    private static function cacheKey(string $dir, string $file): string
    {
        return rtrim(str_replace('\\', '/', $dir), '/') . "\0" . ltrim($file, '/\\');
    }

    /** Onbellegi (ve boylece tum dosyalarin sonucunu) sifirlar. */
    public static function resetCache(): void
    {
        self::$cache = [];
    }
}
