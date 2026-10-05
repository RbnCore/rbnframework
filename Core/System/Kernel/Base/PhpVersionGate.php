<?php

namespace Rbn\Framework\Core\System\Kernel\Base;

/**
 * FW-CANLI-ONCESI-2 — PHP surum kapisi (PHP >= 8.3).
 *
 * AMAC: Canli sunucuda PHP surumu belirlenemiyor (jailed dizinler, .htaccess'te
 * `#php_version` yok, SSH kapali). Yanlis surumde eski PHP bir "Parse error"
 * ya da daha kotusu, satiri olan bir "Call to undefined function" gosterir;
 * operator bunu "site bozuk" sanar. Bu kapi o durumu ACIK ve GUVENLI bir
 * mesaja cevirir.
 *
 * == SOZDIZIMI KURALI (EN ONEMLI KISIM) ==
 *   Bu dosya PHP 7.x ve 8.0/8.1/8.2'de PARSE HATASI VERMEMELIDIR. Aksi halde
 *   kapinin amaci basarisiz olur: 8.3'e ozgu sozdizimi kullanilirsa dosya
 *   hic yuklenemeden PHP teknik bir parse error basar ve operatorun okuyacagi
 *   bakim mesaji yerine o hata gorunur.
 *
 *   Bu yuzden burada:
 *     - strict_types bildirimi YOK,
 *     - parametre ve donus tip imzalari YOK,
 *     - 8.0+ operatörleri (nullsafe operator, match ifadesi) YOK,
 *     - 8.1+ ozellikleri (readonly, enum) YOK,
 *     - null birlestirme kisayolu yerine `isset()` YOK,
 *     - yalnizca PHP 5.4+ sozdizimi kullanilir (sinif sabiti, `static::`,
 *       `__DIR__`, `array()`).
 *
 *   Ayrica bu dosya PSR-4 autoloader'dan ONCE de yuklenebilir: hicbir parent,
 *   trait ya da interface referansi YOKTUR. Bu yuzden `require_once` ile
 *   dogrudan yuklenebilir.
 *
 * KAPI NEREDE ACILIR (uc nokta, en erkenden en geceye):
 *   1. domains/<site>/index.php  — vendor/autoload.php require EDILMEDEN once
 *   2. rbn (CLI)                 — vendor/autoload.php require EDILMEDEN once
 *   3. Bootstrap::run() ve PreBoot::orchestrate() — autoload sonrasi savunma
 *
 * BASARISIZLIK DAVRANISI (fail-closed):
 *   - Web (SAPI != cli): HTTP 503 + genel, yol/proje/DB adi SIZDIRMAYAN metin.
 *   - CLI: STDERR'a net hata + exit(1).
 *   - `headers_sent()` ise baslik basilamaz; yalniz govde basilir.
 *
 * @see \Rbn\Framework\Core\System\Kernel\Bootstrap::run()
 * @see \Rbn\Framework\Core\System\Kernel\Base\PreBoot::orchestrate()
 */
class PhpVersionGate
{
    /** Asgari surum: PHP 8.3.0 -> 8 * 10000 + 3 * 100 + 0 = 80300 */
    const MIN_VERSION_ID = 80300;

    /** Insan tarafindan okunabilen asgari surum. */
    const MIN_VERSION_LABEL = '8.3';

    /**
     * SAYISAL surum kodu yeterli mi? (yan etkisiz, test edilebilir)
     *
     * @param int $versionId PHP_VERSION_ID bicimli surum kodu
     * @return bool
     */
    public static function isSupported($versionId)
    {
        // Bozuk / 0 / bos girdi KAPALI (fail-closed): "bilmiyorum" demek
        // "gecerli" demek DEGILDIR.
        if (!is_int($versionId) && !is_string($versionId)) {
            return false;
        }

        $normalized = trim((string) $versionId);

        if ($normalized === '' || !preg_match('/^[0-9]+$/', $normalized)) {
            return false;
        }

        return (int) $normalized >= self::MIN_VERSION_ID;
    }

    /**
     * 80335 -> "8.3", 70433 -> "7.4", 100000 -> "10.0"
     *
     * @param int $versionId
     * @return string
     */
    public static function versionLabel($versionId)
    {
        $normalized = trim((string) $versionId);

        if ($normalized === '' || !preg_match('/^[0-9]+$/', $normalized)) {
            return 'bilinmiyor';
        }

        $id = (int) $normalized;
        $major = (int) floor($id / 10000);
        $minor = (int) floor(($id % 10000) / 100);

        return $major . '.' . $minor;
    }

    /**
     * Tek dogruluk kaynagi: operator mesaji. YOL, PROJE ADI, DB ADI veya
     * istisna ayrintisi ICERMEZ (sizinti sinifi degil, bakim sinifi olmali).
     *
     * @param int $versionId bulunan surum
     * @return string
     */
    public static function message($versionId)
    {
        return 'Bu uygulama PHP ' . self::MIN_VERSION_LABEL
            . ' veya uzerini gerektirir. Sunucudaki PHP surumu: '
            . self::versionLabel($versionId)
            . '. Lutfen hosting panelinden PHP surumunu '
            . self::MIN_VERSION_LABEL
            . ' olarak ayarlayin ve sayfayi yenileyin.';
    }

    /**
     * KAPI. Gecerliyse true doner ve akis devam eder; degilse sureci
     * durdurur (web: HTTP 503, CLI: net hata + exit(1)).
     *
     * @param int|null    $versionId test icin sahte surum; null ise gercek surum
     * @param string|null $sapi      test icin sahte SAPI; null ise gercek SAPI
     * @return bool
     */
    public static function enforce($versionId = null, $sapi = null)
    {
        $found = ($versionId === null) ? PHP_VERSION_ID : $versionId;

        if (self::isSupported($found)) {
            return true;
        }

        // Basarisizlikta asagida surec sonlanir; ayni istek icin ikinci bir
        // cikti uretilmesi mumkun DEGILDIR.
        if ($sapi === null) {
            $sapi = PHP_SAPI;
        }

        $body = self::message($found);

        if ($sapi === 'cli' || $sapi === 'phpdbg') {
            // Konsol: temiz, kirletmemis, makine tarafindan okunabilir hata.
            if (defined('STDERR')) {
                fwrite(STDERR, '[RBN] PHP surum kapisi: ' . $body . PHP_EOL);
            } else {
                echo '[RBN] PHP surum kapisi: ' . $body . PHP_EOL;
            }

            exit(1);
        }

        // Web: 503 + genel metin. Basliklar gonderildiyse yalniz govde basilir.
        if (headers_sent() === false) {
            http_response_code(503);
            header('Content-Type: text/plain; charset=utf-8');
            header('Cache-Control: no-store, no-cache, must-revalidate');
        }

        echo "HTTP 503 - Service Unavailable\n";
        echo $body . "\n";

        exit(1);
    }
}
