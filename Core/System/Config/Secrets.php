<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\System\Config;

use Rbn\Framework\Core\System\Config\Definitions\SecretsSchema;
use Rbn\Framework\Core\System\Config\Engine\Config\ConfigFileLoader;
use Rbn\Framework\Core\System\Config\Engine\Secrets\SecretsFlatApi;
use Rbn\Framework\Core\System\Config\Engine\Secrets\SecretsLoader;
use Rbn\Framework\Core\System\Config\Engine\Secrets\SecretsSections;
use Rbn\Framework\Core\System\Config\Engine\Secrets\SecretsValidator;

// Bu okuyucu kernel/bootstrap asamasinda calisir; autoloader henuz hazir
// olmayabilir ve birim testleri bu dosyayi TEK BASINA izole bir dizine
// kopyalip dogrudan `require` eder. Bu yuzden komsu dosyalar goreli
// `require_once` ile bulunur.
require_once __DIR__ . '/Engine/Config/ConfigFileGuard.php';
require_once __DIR__ . '/Engine/Config/ConfigFileLoader.php';
require_once __DIR__ . '/Definitions/SecretsSchema.php';
require_once __DIR__ . '/Engine/Secrets/SecretsLoader.php';
require_once __DIR__ . '/Engine/Secrets/SecretsValidator.php';
require_once __DIR__ . '/Engine/Secrets/SecretsSections.php';
require_once __DIR__ . '/Engine/Secrets/SecretsFlatApi.php';

/**
 * Secrets - TÜM Bağlantı DEĞERLERİNİN ve SIRLARIN TEK OKUYUCUSU (fail-closed)
 *
 * [FW-TEK-SECRETS-DOSYASI-161] Patronun isteği (2026-10-02):
 * "TEK 1 ANA dosya olacak, şifreleri HEPSİNİ oraya yazacağım."
 *
 * TEK ÇIKIŞ:
 *   Secrets::masterDb()     -> ['host','port','name','user','pass']
 *   Secrets::smtp()         -> ['enabled','host','port','secure','user','pass',
 *                               'from_address','from_name']
 *   Secrets::cpanel()       -> ['cpanel_host','cpanel_user','cpanel_token']
 *   Secrets::appKey()       -> uygulama şifreleme anahtarı
 *   Secrets::api('iyzico')  -> ['api_key' => ..., 'secret_key' => ...]
 *   Secrets::section($ad)   -> bölümün tamamı (genel erişim)
 *
 * BU DOSYA İNCE CEPHEDİR (yalnız genel metot imzaları, hiçbiri değişmedi):
 *   Definitions/SecretsSchema -> yalnız `public const` (metot YOK)
 *   Engine/Secrets/SecretsLoader    -> yol çözümü + ham dosya okuma (TEK okuma kapısı)
 *   Engine/Secrets/SecretsValidator -> bölüm adı + okunabilirlik doğrulaması
 *   Engine/Secrets/SecretsSections  -> bölüm okuma/doğrulama + düz anahtar haritaları
 *   Engine/Config/ConfigFileLoader   -> ortak dosya tekniği (bul → require → doğrula)
 *   Engine/Config/ConfigFileGuard    -> 0600 izin + `<?php`/`return` sızıntı koruması
 *
 * `ConfigFileLoader` ve `SecretsFlatApi` trait'leri **geriye uyum** için
 * `use` edilir: `guardFileMode()`/`guardFileContent()` birim testi tarafından
 * yansımayla (`ReflectionMethod`) çağrıldığı için, düz anahtar API'si ise
 * mevcut çağıranlar için `Secrets` üzerinde KALMAYA devam eder.
 *
 * TEK DOSYA: `Core/System/Config/Secrets/secrets.php` (şablon:
 * `Secrets/secrets.example.php`; gerçek dosya `.gitignore`'ludur). Bölümler:
 * `master_db`, `smtp`, `cpanel`, üst düzey `app_key`, `api` {hizmet => alanlar}.
 * GERİYE UYUM **YOKTUR** (bilinçli karar): önceki iki ayrı sır dosyası ve
 * "geriye uyum" kipi TAMAMEN KALDIRILDI.
 *
 * API SABİTLERİ (kabul testleriyle sabitlenmiş - İZMALİYOR): `cpanel()`
 * döndürdüğü dizinin anahtarları `host/user/token` DEĞİL,
 * `cpanel_host/cpanel_user/cpanel_token` şeklindedir.
 *
 * FAIL-CLOSED / GÜVENLİK:
 * - Dosya yok / bozuk / izin fazla geniş / zorunlu alan eksik / `CHANGE_ME` /
 *   zorunlu alan boş  -> açık `RuntimeException`. Sessiz `root` / `''`
 *   fallback **YOKTUR**.
 * - Hata metni YALNIZ dosya/bölüm/alan ADINI yazar; HİÇBİR sır değeri yazmaz.
 *   Örnek: `secrets.php: master_db.user tanimli degil.`
 * - `master_db.pass` ve `smtp.pass` BOŞ OLABİLİR (yerelde master DB parolası
 *   gerçekten `''`); `cpanel` ve `api` değerleri BOŞ OLAMAZ.
 * - LAZY: dosya yalnız ilk istendiğinde okunur.
 *
 * SSOT istisnası: bu okuyucu kernel/bootstrap aşamalarında çalışır; bu yüzden
 * ortam servis / IoC / Paths KULLANMAZ, yalnız `__DIR__` ile göreli çözülen
 * komsu dosyaları `require_once` eder. Böylece dosya TEK BAŞINA da çalışır
 * (izole kopyalar, bootstrap).
 *
 * @see \Rbn\Framework\Core\System\Config\Definitions\SecretsSchema
 * @see \Rbn\Framework\Core\System\Config\Engine\Secrets\SecretsLoader
 * @see \Rbn\Framework\Core\System\Config\Engine\Secrets\SecretsValidator
 * @see \Rbn\Framework\Core\System\Config\Engine\Secrets\SecretsSections
 * @see \Rbn\Framework\Core\System\Config\Engine\Config\ConfigFileLoader
 */
final class Secrets
{
    use ConfigFileLoader;

    /**
     * Düz anahtar (eski) okuma yüzeyi: `all()`, `get()`, `masterDbPass()`,
     * `masterSmtpPass()`, `optional()` — `Engine/Secrets/SecretsFlatApi` trait'i.
     * İmzalar `Secrets` üzerinde KORUNUR, çağıranlar kırılmaz.
     */
    use SecretsFlatApi;

    /* ==========================================================================
       [ BÖLÜMLÜ API - TEK ÇIKIŞ ]
       ========================================================================== */

    /**
     * Bir bölümün tamamını döndürür (`master_db`, `smtp`, `cpanel`, `api`).
     *
     * @throws \RuntimeException Bölüm/alan yoksa, dosya okunamazsa, değer
     *                          `CHANGE_ME` veya zorunlu boş ise.
     */
    public static function section(string $name): array
    {
        $name = SecretsValidator::sectionName($name);

        if ($name === SecretsSchema::API_SECTION) {
            return SecretsSections::readAll();
        }

        return SecretsSections::read($name);
    }

    /**
     * Master (sistem) veritabanı bağlantı DEĞERLERİNİN TAMAMI.
     *
     * @return array<string,string> host, port, name, user, pass
     */
    public static function masterDb(): array
    {
        return SecretsSections::read('master_db');
    }

    /**
     * SMTP ayarlarının TAMAMI (bağlantı + gönderen kimliği dâhil).
     *
     * @return array<string,string> enabled, host, port, secure, user, pass,
     *                               from_address, from_name
     */
    public static function smtp(): array
    {
        return SecretsSections::read('smtp');
    }

    /**
     * cPanel kimlik bilgileri - **DÜZ ANAHTAR BİÇİMİ** (kabul testiyle sabitli).
     *
     * @return array{cpanel_host:string,cpanel_user:string,cpanel_token:string}
     *
     * NEDEN `section('cpanel')` DEĞİL: `cpanel()` döndürdüğü dizinin anahtarları
     * kabul testleriyle (`B1-cpanel-sir-koddan-cikar.php`) ve proje koduyla
     * SABİTLENMİŞTİR; bu dosyada değiştirilemez. Bölümün kanonik (düz) biçimi
     * `Secrets::section('cpanel')` / `CPanelProvider` üzerinden
     * okunur.
     */
    public static function cpanel(): array
    {
        $section = SecretsSections::read('cpanel');

        return [
            'cpanel_host' => $section['host'],
            'cpanel_user' => $section['user'],
            'cpanel_token' => $section['token'],
        ];
    }

    /**
     * TEK bir dış servis (ödeme, SMS, ...) için API kimlik bilgileri.
     *
     * @param string $name `api` altındaki hizmet adı (`iyzico`).
     * @return array<string,string> Tanımlı tüm anahtarlar (hiçbiri boş olamaz).
     * @throws \RuntimeException Hizmet tanımlı değilse / anahtar boş veya `CHANGE_ME` ise.
     */
    public static function api(string $name): array
    {
        $name = SecretsValidator::sectionName($name);
        $all = SecretsSections::readAll();

        if (!array_key_exists($name, $all)) {
            throw new \RuntimeException(
                'RBN Guvenlik: ' . SecretsLoader::fileName() . ': api.' . $name . ' tanimli degil. '
                . SecretsLoader::hint(SecretsLoader::displayPath())
            );
        }

        return $all[$name];
    }

    /**
     * Uygulama şifreleme anahtarı (`CryptoHelper`).
     *
     * KAYNAK: **yalnız** `secrets.php` üst düzey `app_key` alanı. Ortam
     * değişkeni (`APP_KEY`/`ENCRYPTION_KEY`) bu okuyucu içinde BİR YOL
     * DEĞİLDİR; `CryptoHelper` kendi `Env` okumasını kendi yolunda yapar.
     *
     * @throws \RuntimeException Anahtar tanımlı değilse (fail-closed).
     */
    public static function appKey(): string
    {
        $value = SecretsSections::topLevel(SecretsSchema::APP_KEY_NAME);

        if ($value === null || $value === '') {
            throw new \RuntimeException(
                'RBN Guvenlik: ' . SecretsLoader::fileName() . ': ' . SecretsSchema::APP_KEY_NAME
                . ' tanimli degil (uygulama sifreleme anahtari). '
                . 'Gomulu/sabit tuz KULLANILMAZ. ' . SecretsLoader::hint(SecretsLoader::displayPath())
            );
        }

        return $value;
    }

    /* ==========================================================================
       [ cPANEL KISA YOLLARI + TEST YARDIMCILARI ]
       ========================================================================== */

    /** cPanel sunucu adı (örn. `www.rbncore.tr`) - port EKLEME. */
    public static function cpanelHost(): string
    {
        return SecretsSections::read('cpanel')['host'];
    }

    /** cPanel kullanıcı adı. */
    public static function cpanelUser(): string
    {
        return SecretsSections::read('cpanel')['user'];
    }

    /** cPanel parolası veya API token'i. */
    public static function cpanelToken(): string
    {
        return SecretsSections::read('cpanel')['token'];
    }

    /**
     * Yalnızca test/doğrulama için: sır dizinini geçici bir dizine bağlar.
     *
     * `null` verilirse gerçek dizine (`Core/System/Config`) dönülür.
     * Önbellek de sıfırlanır.
     */
    public static function setTestDirectory(?string $directory): void
    {
        SecretsLoader::setDirectory($directory);
    }

    /**
     * Önbelleği sıfırlar.
     *
     * NOT: Trait'in kendi `$cache`'i değil, **TEK okuma kapısı** olan
     * `SecretsLoader` önbelleği sıfırlanır (birim testleri statik önbellek
     * sızmasına güvenerek buradan döner).
     */
    public static function resetCache(): void
    {
        SecretsLoader::resetCache();
    }
}
