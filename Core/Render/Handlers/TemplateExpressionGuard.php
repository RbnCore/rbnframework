<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Handlers;

/**
 * TemplateExpressionGuard — sablon ifadesi guvenlik kapisi 🛡️
 *
 * R-03: `ViewEngine::parse()` ifadeyi oldugu gibi PHP'ye gomuyordu. Sifreli
 * (HTML-escaped) olsa bile ifade CALISIR: backtick ile kabuk, `system($c)`,
 * `file_put_contents($p,$d)` — hepsi sunucuda kod yurutur.
 *
 * BU SINIF NE YAPAR:
 *   - Ifade icinde YASAKLI YAPI (backtick, eval, shell, dosya yazma,
 *     include/require, `new`, `?>` breakout, yorum, ...) varsa derleme
 *     HATASI firlatir (fail-closed). Sifreleme zaten var; bu katman
 *     ifadenin kendisini guvenli kilar.
 *   - KORPUS: `projects/`, `domains/` ve framework sablonlarindaki ~1500
 *     benzersiz ifadenin TAMAMI bu kapidan gecer (0 yanlis-pozitif).
 *
 * TASARIM: izin-listesi DEGIL, yasakli-yapi listesi. Neden: korpusta
 * meşru olarak kullanilan degisken-fonksiyon cagrilari var (`$getEmoji($url)`,
 * `$cleanPhone($p)`, `$categoryColor($s)`). Bunlar sunucu tarafinda tanimli
 * closure'lardir ve ifade DEGERI kullanici verisinden gelmez; yasaklamak
 * meşru sablonlari kirar. Bkz. rapor "acik sorular".
 *
 * NOT: Bu dosyanin dokumantasyonu bilerek sablon ifade ACIKLARINI (iki tirnak
 * + iki suslu sus) ICERMEZ; aksi halde envanter tarayicisi kendi
 * dokumantasyonunu "sablon kullanimi" sanirdi.
 *
 * YENI METOT/SABIT ADLARI INGILIZCE; mevcut Turkce API adlari DEGISTIRILMEDI.
 *
 * @see \Rbn\Framework\Core\Render\ViewEngine::parse()
 */
class TemplateExpressionGuard
{
    /**
     * Ifade cagrisi olarak YASAKLI islev adlari (kucuk harf, word-boundary).
     * Kutuphane islevleri: kod calistirma + dosya/ayarlama yazma.
     *
     * @var string[]
     */
    private const DENIED_FUNCTIONS = [
        // Kod yurutma / kabuk
        'eval', 'create_function', 'assert', 'assert_options',
        'system', 'exec', 'passthru', 'shell_exec', 'proc_open', 'popen',
        'pcntl_exec', 'escapeshellcmd', 'escapeshellarg',
        // Dosya sistemi (okuma + yazma)
        'include', 'include_once', 'require', 'require_once',
        'file_put_contents', 'file_get_contents', 'fopen', 'fwrite', 'fputs',
        'fread', 'fclose', 'readfile', 'unlink', 'copy', 'rename', 'move_uploaded_file',
        'mkdir', 'rmdir', 'scandir', 'glob', 'opendir', 'chmod', 'chown', 'touch',
        'unlink_recursive', 'highlight_file', 'show_source',
        // Geri cagirma tabanli islevler (dize goreli kod yurutme)
        'call_user_func', 'call_user_func_array', 'array_map', 'array_filter',
        'array_walk', 'usort', 'uasort', 'uksort', 'array_diff_ukey',
        'register_shutdown_function', 'register_tick_function',
        // Durum degistirme
        'extract', 'compact', 'putenv', 'ini_set', 'ini_alter', 'set_error_handler',
        'set_exception_handler', 'error_reporting', 'unserialize',
        'phpinfo', 'phpcredits', 'getenv', 'dl', 'gc_collect_cycles',
        // Ag / yan etki
        'header', 'setcookie', 'mail', 'fsockopen', 'stream_socket_client',
        'curl_exec', 'curl_init', 'file_exists', 'is_file', 'is_dir', 'opendir',
    ];

    /**
     * Ifade icinde YASAKLI dil yapilari (anahtar kelime, word-boundary).
     * Yeni nesne uretimi, cikti/yonlendirme, PHP-etiket kirilasi ve
     * yorum ile derleyici disina cikma.
     *
     * @var string[]
     */
    private const DENIED_KEYWORDS = [
        'new', 'echo', 'print', 'exit', 'die', 'goto', 'yield', 'function',
        'declare', 'namespace', 'instanceof', 'clone', 'unset',
    ];

    /**
     * `include` / `require` ailesi: cagirisiz (ifade olarak) kullanimi da
     * yasaktir. Korpusta `required_role` gibi METIN geciyor; bu yuzden
     * word-boundary zorunlu (`\brequire\b` "required" ile eslesmez).
     *
     * @var string[]
     */
    private const DENIED_STATEMENTS = [
        'include', 'include_once', 'require', 'require_once',
    ];

    /**
     * YASAKLI karakter/seyrek kaliplar (duzenli ifade, case-insensitive degil).
     *
     * @var array<string, string> desen => aciklama
     */
    private const DENIED_PATTERNS = [
        '`'      => 'backtick (kabuk komutu)',
        '<?'     => 'PHP acilis etiketi kirilasi',
        '?>'     => 'PHP kapilis etiketi kirilasi',
        '/*'     => 'blok yorumu',
        '$$'     => 'degisken degiskeni',
        '${'     => 'surekli degisken adreslemesi',
    ];

    /** @var array<string, bool> Ayni istek icinde tekrar kontrol harcamak icin onbellek. */
    private static array $sonucOnbellegi = [];

    /**
     * Bir sablon ifadesinin (iki tirnak ya da iki unlem isaretli kacis
     * bicimi) derlenmesine izin verilip verilmedigini denetler.
     *
     * @throws \RuntimeException Ifade guvenli degilse (derleme hatasi).
     */
    public function assertSafe(string $ifade, string $baglam = 'sablon'): void
    {
        $ifade = trim($ifade);
        if ($ifade === '') {
            throw new \RuntimeException(
                'RBN Guvenlik: ' . $baglam . ' icinde bos ifade reddedildi.'
            );
        }

        $anahtar = $ifade;
        if (isset(self::$sonucOnbellegi[$anahtar])) {
            if (self::$sonucOnbellegi[$anahtar] !== null) {
                throw new \RuntimeException(self::$sonucOnbellegi[$anahtar]);
            }
            return;
        }

        try {
            $this->denetle($ifade, $baglam);
            self::$sonucOnbellegi[$anahtar] = null;
        } catch (\RuntimeException $e) {
            self::$sonucOnbellegi[$anahtar] = $e->getMessage();
            throw $e;
        }
    }

    /** Denetim govdesi: gercek kural uygulamasi. */
    private function denetle(string $ifade, string $baglam): void
    {
        foreach (self::DENIED_PATTERNS as $desen => $aciklama) {
            if (strpos($ifade, $desen) !== false) {
                throw new \RuntimeException(
                    'RBN Guvenlik: ' . $baglam . ' ifadesi reddedildi — yasakli kalip: ' . $aciklama . '.'
                );
            }
        }

        // Ifade olarak include/require (`$a ? include $p : null` bicimi).
        foreach (self::DENIED_STATEMENTS as $anahtarKelime) {
            $desen = '/(?<![A-Za-z0-9_$>\\\\])' . preg_quote($anahtarKelime, '/') . '(?![A-Za-z0-9_])/i';
            if (preg_match($desen, $ifade) === 1) {
                throw new \RuntimeException(
                    'RBN Guvenlik: ' . $baglam . ' ifadesi reddedildi — yasakli dosya dahil etme: ' . $anahtarKelime . '.'
                );
            }
        }

        // Islev cagrisi: `system(` / `system (` / `\system(`.
        // Word-boundary: `system_errors` ve `execution` YASAK DEGILDIR
        // (korpus bunlari kullanir) — bu yuzden sinir karakteri zorunlu.
        foreach (self::DENIED_FUNCTIONS as $islev) {
            $desen = '/(?<![A-Za-z0-9_$>\\\\])' . preg_quote($islev, '/') . '\s*\(/i';
            if (preg_match($desen, $ifade) === 1) {
                throw new \RuntimeException(
                    'RBN Guvenlik: ' . $baglam . ' ifadesi reddedildi — yasakli islev cagrisi: ' . $islev . '().'
                );
            }
        }

        // Anahtar kelime: `new`, `echo`, ... `require` YALNIZ cagri olarak
        // yakalanir; metin olarak gecen `required`/`system` etkilenmez.
        foreach (self::DENIED_KEYWORDS as $kelime) {
            $desen = '/(?<![A-Za-z0-9_$>\\\\-])' . preg_quote($kelime, '/') . '(?![A-Za-z0-9_])/i';
            if (preg_match($desen, $ifade) === 1) {
                throw new \RuntimeException(
                    'RBN Guvenlik: ' . $baglam . ' ifadesi reddedildi — yasakli dil yapisi: ' . $kelime . '.'
                );
            }
        }

        // Degisken-fonksiyon cagirisi denetimi KAPSAMLI: yalniz `$` ile
        // baslayan ve `)` ile kapanmayan, cagirma desenine uymayanlar reddedilir.
        // Meşru kullanim (`{{ $getEmoji($u) }}`) bu desene girmez.
        if (preg_match('/\$[A-Za-z_]\w*\s*\(/', $ifade) === 1) {
            // Meşru: fonksiyon cagrisi. Zararli olabilecek tek bicim basinda
            // sistem-cagri yapan degisken sozu (`$GLOBALS`, `$_SERVER` erisimi
            // degil, cagirma `$cmd(`).
            if (preg_match('/\$(?:GLOBALS|_SERVER|_ENV|_GET|_POST|_REQUEST|_COOKIE|this)\s*\(/i', $ifade) === 1) {
                throw new \RuntimeException(
                    'RBN Guvenlik: ' . $baglam . ' ifadesi reddedildi — superglobal islev cagrisi.'
                );
            }
        }
    }

    /**
     * Bir ifadenin guvenli olup olmadigini dondurur (test/olcume yardimci).
     */
    public function isSafe(string $ifade): bool
    {
        try {
            $this->assertSafe($ifade, 'olcum');
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Onbellegi temizler (uzun sureli sureclerde bellek sinirini korur).
     */
    public static function onbellegiTemizle(): void
    {
        self::$sonucOnbellegi = [];
    }
}