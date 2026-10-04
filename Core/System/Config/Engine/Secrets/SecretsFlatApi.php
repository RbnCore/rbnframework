<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Config\Engine\Secrets;

use Rbn\Framework\Core\System\Config\Definitions\SecretsSchema;

/**
 * SecretsFlatApi - DÜZ ANAHTAR okuma yüzeyi (çağıranları kırmamak için KORUNUR).
 *
 * Bu metotlar bölümlü API öncesinden kalma **düz anahtar** okuma yüzeyidir.
 * Yeni kod BÖLÜM okumalı API'yi kullanmalıdır (`Secrets::masterDb()`,
 * `::smtp()`, `::cpanel()`, `::api()`, `::section()`); bu yüzey yalnız mevcut
 * çağıranların kırılmaması için vardır ve `Secrets` sınıfı üzerinde `use`
 * edilerek **imzaları korunur**.
 *
 * (Eski sır dosyası adları burada BİLEREK anılmaz: tek dosya kuralı
 * `fw_tek_secrets` birim testi bu adların kod ağacında hiç geçmemesini
 * ölçer.)
 *
 * SORUMLULUK: yalnız düz anahtar -> değer eşlemesi üretmek. Doğrulama ve
 * bölüm okuma `Engine/Secrets/SecretsSections`'in işidir; burada KOPYALANMAZ.
 *
 * GUVENLİK: HATA MESAJLARINDA HİÇBİR SIR DEĞERİ YAZILMAZ.
 *
 * @see \Rbn\Framework\Core\System\Config\Secrets
 * @see \Rbn\Framework\Core\System\Config\Engine\Secrets\SecretsSections
 */
trait SecretsFlatApi
{
    /**
     * Master bölümünün eski düz-anahtar haritası.
     *
     * @return array<string,string>
     */
    public static function all(): array
    {
        $masterDb = SecretsSections::read('master_db');

        $out = [SecretsSchema::FLAT_MASTER_PW => $masterDb['pass']];

        if (($masterDb['user'] ?? '') !== '') {
            $out[SecretsSchema::FLAT_MASTER_USER] = $masterDb['user'];
        }

        // Üst düzey ek anahtarlar (`app_key` vb.) aynen korunur.
        foreach (SecretsSections::topLevelScalars() as $key => $value) {
            $out[$key] = $value;
        }

        return $out;
    }

    /**
     * Tek bir değer döndürür (eski düz anahtar adıyla).
     *
     * @throws \RuntimeException Anahtar hiçbir yerde tanımlı değilse.
     */
    public static function get(string $key): string
    {
        $all = self::all();

        if (!array_key_exists($key, $all)) {
            throw new \RuntimeException(
                'RBN Guvenlik: ' . SecretsLoader::fileName() . ': ' . $key . ' tanimli degil. '
                . SecretsLoader::hint(SecretsLoader::displayPath())
            );
        }

        return $all[$key];
    }

    /** Master (sistem) veritabanı parolası. */
    public static function masterDbPass(): string
    {
        return SecretsSections::read('master_db')['pass'];
    }

    /** Master (sistem) SMTP parolası. */
    public static function masterSmtpPass(): string
    {
        return SecretsSections::read('smtp')['pass'];
    }

    /**
     * OPSİYONEL bir değer - **yalnız "TANIMLI DEĞİL" yutar** (`null` döner).
     *
     * [FW-BANU-B4-BARAN] HATA YUTULMAZ: dosya bozuk/okunamaz/geçersiz
     * biçimli/izni fazla geniş ise `RuntimeException` fırlatılır (mesajda sır
     * değeri YOKTUR). Yalnız "bu anahtar tanımlı değil" durumu `null` olur.
     *
     * @throws \RuntimeException Dosya bozuk/okunamaz/geçersiz biçimli ya da
     *                          izni fazla geniş ise (fail-closed).
     */
    public static function optional(string $key): ?string
    {
        if (trim($key) === '') {
            return null;
        }

        $value = SecretsSections::optionalFlat()[$key] ?? null;

        if ($value === null || $value === '' || $value === SecretsSchema::CHANGE_ME) {
            return null;
        }

        return $value;
    }
}
