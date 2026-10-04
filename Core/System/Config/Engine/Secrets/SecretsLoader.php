<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Config\Engine\Secrets;

use Rbn\Framework\Core\System\Config\Definitions\SecretsSchema;
use Rbn\Framework\Core\System\Config\Engine\Config\ConfigFileLoader;

/**
 * SecretsLoader - sır dosyasının **TEK okuma kapısı** (yol çözümü + ham dizi).
 *
 * SORUMLULUK (tek iş): "hangi dosya, hangi dizinden, hangi kullanıcıya görünen
 * yolla okunacak" sorusuna cevap verir ve dosyayı `ConfigFileLoader` zincirinden
 * geçirip **HAM** diziyi döndürür. Doğrulama burada YOKTUR
 * (`Engine/Secrets/SecretsValidator`), bölüm kurgusu burada YOKTUR
 * (`Engine/Secrets/SecretsSections`).
 *
 * BAĞIMLILIK: bu okuyucu kernel/bootstrap aşamasında çalışır; bu yüzden
 * ortam servisi / IoC / Paths KULLANMAZ. Yol `__DIR__` ile göreli çözülür ve
 * `ConfigFileLoader` trait'i `require_once` ile gelir (autoloader henüz yok).
 *
 * GUVENLİK: HATA MESAJLARINDA HİÇBİR SIR DEĞERİ YAZILMAZ; yalnız dosya adı,
 * konum ve ne yapılması gerektiği yazar.
 *
 * @see \Rbn\Framework\Core\System\Config\Secrets
 * @see \Rbn\Framework\Core\System\Config\Engine\Config\ConfigFileLoader
 */
final class SecretsLoader
{
    use ConfigFileLoader;

    /**
     * @var string|null Test için geçici sır dizini (sahte dizin).
     *
     * `null` = gerçek dizin (`Core/System/Config`). Birim testleri gerçek sır
     * dosyalarına DOKUNMADAN davranışı doğrulamak için geçici bir dizine
     * bağlanır.
     */
    private static ?string $directory = null;

    /**
     * Sır dosyasının görelisine eklenen kök dizin.
     *
     * Sabit `/Secrets/...` ile başladığı için bu dizin `Config/` olmalıdır
     * (`Config` + `/Secrets/secrets.php` = doğru yol). Testte geçici bir
     * dizine bağlanabilir.  [CONFIG-E-PELIN] Bu sinif `Config/Engine/Secrets/` altinda durur; kok `Config/` iki seviye yukaridadir (`dirname(__DIR__, 2)`).
     */
    public static function rootDirectory(): string
    {
        return self::$directory ?? dirname(__DIR__, 2);
    }

    /** Dosya adı (`secrets.php`) — hata mesajlarında kullanılır. */
    public static function fileName(): string
    {
        return basename(SecretsSchema::FILE);
    }

    /** Kullanıcının göreceği tek doğru yol. */
    public static function displayPath(): string
    {
        return SecretsSchema::BASE_DIR . '/' . ltrim(SecretsSchema::FILE, '/\\');
    }

    /**
     * Dosyayı okunabilirlik + biçim denetiminden geçirip HAM diziyi döndürür.
     *
     * @return array<string,mixed>
     * @throws \RuntimeException Dosya yoksa / okunamazsa / izin fazla genişse /
     *                          içerik sızdırıyorsa / dizi dönmüyorsa.
     */
    public static function raw(): array
    {
        return self::includeArray(self::rootDirectory(), SecretsSchema::FILE, self::displayPath());
    }

    /** Dosyanın tam yolu (test dizini bağlanmış olabilir). */
    public static function fullPath(): string
    {
        return rtrim(self::rootDirectory(), '/\\') . '/' . ltrim(SecretsSchema::FILE, '/\\');
    }

    /**
     * Dosya hiç tanımlı değil mi? (`optional()` yolunun "tanımsız" ayrımı)
     *
     * DIKKAT: dosya **var** ama okunamaz/bozuk/izni geniş ise bu `false`
     * döner ve çağıran taraf fail-closed hata fırlatır (güvenlik hatası
     * yutulmaz).
     */
    public static function isFileMissing(): bool
    {
        return !is_file(self::fullPath());
    }

    /**
     * Yalnızca test/doğrulama için: sır dizinini geçici bir dizine bağlar.
     *
     * `null` verilirse gerçek dizine (`Core/System/Config`) dönülür.
     * Önbellek de sıfırlanır.
     */
    public static function setDirectory(?string $directory): void
    {
        self::$directory = $directory === null ? null : rtrim($directory, '/\\');
        self::resetCache();
    }

    /** Önbelleği (ve böylece tüm dosyaların sonucunu) sıfırlar. */
    public static function resetCache(): void
    {
        self::$cache = [];
    }
}
