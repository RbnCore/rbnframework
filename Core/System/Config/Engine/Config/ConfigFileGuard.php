<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Config\Engine\Config;

/**
 * ConfigFileGuard - Yapilandirma / sir dosyalarinin IZIN + ICERIK + YOL korumasi.
 *
 * [FW-TEK-SECRETS-DOSYASI-161] Ortak dosya tekniginin "guvenli taraf" buradadir:
 * operator icin yol/ipucu uretimi, izin tavani (0600) ve sizinti onleyen icerik
 * bicim kontrolu. Yukleme zincirinin kendisi `ConfigFileLoader` trait'idir.
 *
 * NEDEN AYRI DOSYA: `ConfigFileLoader` tek basina 250 satiri asacakti; kural
 * "her dosya tek sorumluluk" ve "sabitler/metotlar ayri dosya"dir.
 *
 * GUVENLIK: HATA MESAJLARINDA HICBIR SIR DEGERI YAZILMAZ. Yalnizca dosya adi,
 * konum, bolum/anahtar adi ve ne yapilmasi gerektigi yazar. Boylece hata logu
 * (ve HTTP govdesi) sifre tasimaz.
 *
 * @see \Rbn\Framework\Core\System\Config\Engine\Config\ConfigFileLoader
 * @see \Rbn\Framework\Core\System\Config\Secrets
 */
trait ConfigFileGuard
{
    /* ==========================================================================
       [ YOL YARDIMCILARI ]
       ========================================================================== */

    /**
     * Sablon dosya yolunu uretir: `.../secrets.php` -> `.../secrets.example.php`.
     *
     * `.php.example.php` TUZAGINDAN KACINILIR (FW-110 T-1): son ek `.php`
     * ILE degistirilir, once eklenmez.
     */
    public static function examplePath(string $displayPath): string
    {
        $dir = str_replace('\\', '/', dirname($displayPath));
        $name = preg_replace('/\.php$/', '.example.php', basename($displayPath));

        return ($dir === '' ? '.' : $dir) . '/' . (string) $name;
    }

    /**
     * Operator icin "nasil duzelteyim" ipucu.
     *
     * DIZIN ADI `Secrets` DEGILDIR: bu metin yalniz `*.example.php` gibi
     * SABLON adlarini uretir.
     */
    public static function hint(string $displayPath): string
    {
        return self::examplePath($displayPath)
            . ' dosyasini kopyalayip gercek degerleri yazin (.gitignore zaten filtreliyor).';
    }

    /* ==========================================================================
       [ IZIN + ICERIK KORUMASI ]
       ========================================================================== */

    /** Izin tavani: sadece sahibi okuyabilmeli (0600). */
    private static function permissionCeiling(): int
    {
        return 0600;
    }

    /**
     * Dosya izni kontrolu - yalniz `0600` kabul edilir.
     *
     * PLATFORM:
     *  - Linux/macOS: grup/diger erisimi varsa fail-closed hata.
     *  - Windows: `fileperms()` NTFS ACL'ini TEMSIL ETMEZ (yaygin olarak
     *    0666 doner). Bu yuzden kontrol **ATLANIR** - Windows'ta izin kontrolu
     *    ACL'e bakmadan anlamsizdir ve her yuklemeyi hata ile dusururdu.
     *    Bu yuzden Windows'ta `.gitignore` + sunucuda `chmod 600` operator
     *    sorumlulugundadir.
     */
    public static function guardFileMode(string $file, string $displayPath, string $fileName): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return; // NTFS izinleri ACL ile yonetilir; fileperms() anlamsiz.
        }

        $perms = @fileperms($file);
        if ($perms === false) {
            return; // Dosya sistemi bildirmiyor; icerik kontrolu devam eder.
        }

        $ceiling = self::permissionCeiling();
        $mode = $perms & 0777;
        if (($mode & ~$ceiling & 0777) === 0) {
            return;
        }

        throw new \RuntimeException(
            'RBN Guvenlik: ' . $fileName . ' fazla genis izinli (mod ' . decoct($mode, 8)
            . ', izinlenen en genis 0600). Diger kullanicilar da sir dosyasini okuyabilir. '
            . 'Duzeltme: chmod 600 ' . $displayPath . ';'
        );
    }

    /**
     * Icerik bicim kontrolu: dosya `<?php` ile baslamali ve `return` ifadesi
     * `?>` kapanisindan ONCE gelmeli.
     *
     * GEREKCE (iki ayri sizinti sinifi):
     *  1) `<?php` acilis etiketi YOKSA `require` dosyayi oldugu gibi EKRANA
     *     basar -> sirlar HTTP govdesine sizar.
     *  2) `?>` kapanisi `return`'den ONCE ise, `return` sonrasi kod dosya
     *     sonundaki metin olarak ekrana basilir; `require` `1` dondurur ve
     *     asagidaki "dizi dondurmuyor" mesaji gecersiz/karartici olur.
     */
    public static function guardFileContent(string $file, string $displayPath, string $fileName): void
    {
        $head = @file_get_contents($file, false, null, 0, 8192);
        if ($head === false || $head === '') {
            throw new \RuntimeException(
                'RBN Guvenlik: ' . $fileName . ' okunamadi veya bos. ' . $displayPath
            );
        }

        // BOM ve bastaki bosluklar haric tutulur.
        $head = (string) preg_replace('/^\xEF\xBB\xBF/', '', $head);
        $head = ltrim($head);

        if (!str_starts_with($head, '<?php')) {
            throw new \RuntimeException(
                'RBN Guvenlik: ' . $fileName . ' "<?php" ile baslamiyor - bu bicimde require edilen '
                . 'dosya ekrana yazilir (sirlar sizar). Beklenen: <?php return [ \'anahtar\' => \'deger\' ];'
            );
        }

        $body = substr($head, strlen('<?php'));
        $closing = strpos($body, '?>');
        $returnAt = self::firstReturnPosition($body);

        if ($returnAt === null) {
            throw new \RuntimeException(
                'RBN Guvenlik: ' . $fileName . ' "return" ifadesi icermiyor - dosya dizi dondurmeyen '
                . 'bir deger verir. Beklenen: <?php return [ \'anahtar\' => \'deger\' ];'
            );
        }

        if ($closing !== false && $closing < $returnAt) {
            throw new \RuntimeException(
                'RBN Guvenlik: ' . $fileName . ' "return" ifadesinden once "?>" kapanisi var - '
                . 'dosya sonundaki her sey ekrana basilir. Kapanis etiketini kaldirin.'
            );
        }
    }

    /**
     * Kod govdesindeki ilk ust-duzey `return` ifadesinin konumu.
     *
     * Yorum satirlari (`//`, `#`, `/* ... *\/`) ve tek tirnakli metinler
     * atlanir; boylece yorumda gecen "return" kelimesi yanlis alarm uretmez.
     */
    private static function firstReturnPosition(string $body): ?int
    {
        $len = strlen($body);
        $i = 0;
        while ($i < $len) {
            $c = $body[$i];

            // Tek satir yorumu
            if (($c === '/' && ($body[$i + 1] ?? '') === '/') || $c === '#') {
                $nl = strpos($body, "\n", $i);
                if ($nl === false) {
                    return null;
                }
                $i = $nl + 1;
                continue;
            }

            // Blok yorumu
            if ($c === '/' && ($body[$i + 1] ?? '') === '*') {
                $end = strpos($body, '*/', $i + 2);
                if ($end === false) {
                    return null;
                }
                $i = $end + 2;
                continue;
            }

            // Tek tirnakli metin ('...') - return kelimesi sayilmaz
            if ($c === "'") {
                $i++;
                while ($i < $len) {
                    if ($body[$i] === '\\') {
                        $i += 2;
                        continue;
                    }
                    if ($body[$i] === "'") {
                        $i++;
                        break;
                    }
                    $i++;
                }
                continue;
            }

            // Cift tirnakli metin ("...") - return kelimesi sayilmaz
            if ($c === '"') {
                $i++;
                while ($i < $len) {
                    if ($body[$i] === '\\') {
                        $i += 2;
                        continue;
                    }
                    if ($body[$i] === '"') {
                        $i++;
                        break;
                    }
                    $i++;
                }
                continue;
            }

            if (($c === 'r' || $c === 'R')
                && strcasecmp(substr($body, $i, 6), 'return') === 0
                && !preg_match('/[a-zA-Z0-9_]/', (string) ($body[$i + 6] ?? ' '))
            ) {
                return $i;
            }

            $i++;
        }

        return null;
    }
}
