<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Config\Engine\Secrets;

use Rbn\Framework\Core\System\Config\Definitions\SecretsSchema;
use Rbn\Framework\Core\System\Config\Engine\Config\ConfigFileLoader;

/**
 * SecretsSections - `secrets.php` **bölümlerinin** okuma + doğrulama kurgusu.
 *
 * SORUMLULUK (tek iş): "hangi bölümü/anahtarı okuyacağız ve hangi kural
 * listesine göre doğrulayacağız?" sorusuna cevap verir. Yol çözümü
 * `Engine/Secrets/SecretsLoader`, genel bölüm-adı/izin denetimi
 * `Engine/Secrets/SecretsValidator`, kuralların kendisi `Definitions/SecretsSchema`
 * içindedir.
 *
 * AYRIM NEDEN: anahtar doğrulaması TEK yazımdır (`ConfigFileLoader::validate`).
 * Bu sınıf yalnız "hangi anahtarlar zorunlu" listesini seçer; doğrulama
 * algoritmasını KOPYALAMAZ.
 *
 * GUVENLİK: HATA MESAJLARINDA HİÇBİR SIR DEĞERİ YAZILMAZ; yalnız dosya adı,
 * bölüm/alan adı ve ne yapılması gerektiği yazar.
 *
 * @see \Rbn\Framework\Core\System\Config\Engine\Secrets\SecretsLoader
 * @see \Rbn\Framework\Core\System\Config\Engine\Secrets\SecretsValidator
 * @see \Rbn\Framework\Core\System\Config\Definitions\SecretsSchema
 */
final class SecretsSections
{
    use ConfigFileLoader;

    /**
     * TEK dosyadan TEK bölümü okur ve doğrular.
     *
     * @return array<string,string>
     * @throws \RuntimeException
     */
    public static function read(string $name): array
    {
        $raw = SecretsLoader::raw();
        $display = SecretsLoader::displayPath();
        $file = SecretsLoader::fileName();

        if (!array_key_exists($name, $raw)) {
            throw new \RuntimeException(
                'RBN Guvenlik: ' . $file . ': ' . $name . ' bolumu tanimli degil. '
                . SecretsLoader::hint($display)
            );
        }

        if (!is_array($raw[$name])) {
            throw new \RuntimeException(
                'RBN Guvenlik: ' . $file . ': ' . $name
                . ' bolumu bir dizi degil (bozuk sır dosyasi). ' . SecretsLoader::hint($display)
            );
        }

        /** @var array<string,mixed> $section */
        $section = SecretsLoader::normalize($raw[$name]);

        $rules = SecretsSchema::SECTION_RULES[$name] ?? ['required' => [], 'nonEmpty' => []];
        SecretsLoader::validate(
            $section,
            $rules['required'],
            $rules['nonEmpty'],
            $file . ': ' . $name,
            $display
        );

        return $section;
    }

    /**
     * Dosyanın `api` bölümü: hizmet adı => (anahtar => değer).
     *
     * @return array<string,array<string,string>>
     * @throws \RuntimeException
     */
    public static function readAll(): array
    {
        $raw = SecretsLoader::raw();

        if (!isset($raw[SecretsSchema::API_SECTION]) || !is_array($raw[SecretsSchema::API_SECTION])) {
            throw new \RuntimeException(
                'RBN Guvenlik: ' . SecretsLoader::fileName() . ': api bolumu tanimli degil. '
                . SecretsLoader::hint(SecretsLoader::displayPath())
            );
        }

        $display = SecretsLoader::displayPath();
        $file = SecretsLoader::fileName();
        $out = [];

        foreach ($raw[SecretsSchema::API_SECTION] as $name => $row) {
            $name = (string) $name;
            if (!is_array($row)) {
                throw new \RuntimeException(
                    'RBN Guvenlik: ' . $file . ': api.' . $name . ' bir dizi degil. '
                    . SecretsLoader::hint($display)
                );
            }

            $flat = SecretsLoader::normalize($row);
            $label = $file . ': api.' . $name;

            if ($flat === []) {
                throw new \RuntimeException(
                    'RBN Guvenlik: ' . $label . ' bos birakilmis. ' . SecretsLoader::hint($display)
                );
            }

            // API kimlik bilgisi: tanımlı her anahtar zorunlu ve boş olamaz.
            SecretsLoader::validate($flat, array_keys($flat), array_keys($flat), $label, $display);

            $out[$name] = $flat;
        }

        return $out;
    }

    /**
     * Üst düzey (bölüm olmayan) SKALER anahtarlar.
     *
     * @return array<string,string>
     */
    public static function topLevelScalars(): array
    {
        $out = [];

        foreach (SecretsLoader::raw() as $key => $value) {
            if (is_array($value) || !is_scalar($value)) {
                continue; // Bölümler üst düzey anahtar değildir.
            }
            $out[(string) $key] = (string) $value;
        }

        return $out;
    }

    /** Üst düzey tek anahtar (`app_key`). `CHANGE_ME` `null` sayılır. */
    public static function topLevel(string $key): ?string
    {
        $value = self::topLevelScalars()[$key] ?? null;

        if ($value === null || $value === '' || $value === SecretsSchema::CHANGE_ME) {
            return null;
        }

        return $value;
    }

    /**
     * `optional()` için düz-anahtar haritası — `all()` ile **AYNI anahtar
     * adlarını** üretir, ama zorunlu bölüm/alan doğrulamasını UYGULAMAZ.
     *
     * NEDEN AYRI YOL: `all()` `master_db`/`smtp` bölümlerini zorunlu sayar.
     * `optional('app_key')` gibi bir OPSİYONEL arama, bu zorunlu bölümlerin
     * yokluğu yüzünden dosyada **gerçekten tanımlı** olan anahtarı `null`
     * yapardı (yanlış teşhis). Burada yalnız **dosya seviyesindeki** sorunlar
     * (okunamama / izin / biçim) fail-closed olur; **içerik eksikliği "tanımlı
     * değil"** sayılır.
     *
     * @return array<string,string>
     * @throws \RuntimeException Dosya bozuk/okunamaz/geçersiz biçimli ise.
     */
    public static function optionalFlat(): array
    {
        // Dosya HİÇBİR YERDE tanımlı değilse bu "tanımsız"dır -> `null`.
        // (Dosya VARSA ama okunamaz/bozuk/izni geniş ise fail-closed olur.)
        if (SecretsLoader::isFileMissing()) {
            return [];
        }

        SecretsValidator::readable(
            SecretsLoader::fullPath(),
            SecretsLoader::displayPath(),
            SecretsLoader::fileName()
        );

        $raw = SecretsLoader::raw();
        $out = [];

        foreach (SecretsSchema::OPTIONAL_SECTIONS as $name) {
            if (!array_key_exists($name, $raw) || !is_array($raw[$name])) {
                continue; // Bölüm tanımlı değil = opsiyonel aramada sorun DEĞİL.
            }

            /** @var array<string,mixed> $section */
            $section = SecretsLoader::normalize($raw[$name]);

            if (array_key_exists('pass', $section)) {
                $out[$name === 'master_db'
                    ? SecretsSchema::FLAT_MASTER_PW
                    : SecretsSchema::FLAT_SMTP_PW] = $section['pass'];
            }

            if ($name === 'master_db') {
                $user = $section['user'] ?? '';
                if ($user !== '' && $user !== SecretsSchema::CHANGE_ME) {
                    $out[SecretsSchema::FLAT_MASTER_USER] = $user;
                }
            }
        }

        // Üst düzey ek anahtarlar (`app_key` vb.) aynen korunur.
        foreach (self::topLevelScalars() as $k => $v) {
            $out[$k] = $v;
        }

        return $out;
    }
}
