<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Config\Engine\Secrets;

/**
 * SecretsValidator - sır dosyasının **TEK doğrulama katmanı** (fail-closed).
 *
 * SORUMLULUK (tek iş): "okunan değerler kabul edilebilir mi?" sorusuna cevap
 * verir. Okuma yoktur (`Engine/Secrets/SecretsLoader`), bölüm kurgusu yoktur
 * (`Engine/Secrets/SecretsSections`), sabitler yoktur (`Definitions/SecretsSchema`).
 *
 * KURALLAR (ihlal edilirse `RuntimeException`, sessiz devam YOK):
 *  1. Bölüm/hizmet adı geçersizse       -> RuntimeException
 *  2. Zorunlu anahtar tanımlı değilse   -> RuntimeException
 *  3. Değer `CHANGE_ME` ise             -> RuntimeException
 *  4. Boş değer reddedilen alansa       -> RuntimeException
 *  5. Dosya var ama OKUNAMAZ ise        -> RuntimeException
 *
 * GUVENLİK: HATA MESAJLARINDA HİÇBİR SIR DEĞERİ YAZILMAZ. Yalnız dosya adı,
 * bölüm/alan adı ve ne yapılması gerektiği yazar. Böylece hata logu (ve HTTP
 * gövdesi) şifre taşımaz.
 *
 * @see \Rbn\Framework\Core\System\Config\Engine\Secrets\SecretsLoader
 * @see \Rbn\Framework\Core\System\Config\Secrets
 */
final class SecretsValidator
{
    /**
     * Bölüm/hizmet adı biçim doğrulaması.
     *
     * Kullanıcı girdisi (hizmet adı) dosya yoluna girdiği için yalnız
     * `harf/rakam/altçizgi` kabul edilir; böylece `../../etc/passwd` gibi
     * değerler hiçbir işlem görmez.
     *
     * @throws \RuntimeException Ad boş veya geçersizse.
     */
    public static function sectionName(string $name): string
    {
        $name = trim($name);

        if ($name === '' || !preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            throw new \RuntimeException(
                'RBN Guvenlik: gecersiz bolum adi. Yalniz harf, rakam ve altcizgi kullanilabilir.'
            );
        }

        return $name;
    }

    /**
     * Dosya **VAR** ama okunamaz (izin) — `includeArray()` bunu "bulunamadı"
     * diye bildirir; `optional()` için bu "tanımsız" DEĞİL, **güvenlik hatası**
     * sayılmalı (axi belirsizliği ile fail-closed).
     *
     * DIKKAT: dosya **hiç yoksa** burada hata ATILMAZ — "sır tanımlı değil"
     * durumudur ve `optional()` `null` dönmelidir. Yalnız `is_file()` DOĞRU
     * ama `is_readable()` YANLIŞ ise (izin) güvenlik hatası sayılır.
     *
     * @throws \RuntimeException Dosya var ama okunamıyorsa.
     */
    public static function readable(string $fullPath, string $displayPath, string $fileName): void
    {
        if (!is_file($fullPath) || is_readable($fullPath)) {
            return;
        }

        throw new \RuntimeException(
            'RBN Guvenlik: ' . $fileName . ' bulundu ama OKUNAMADI (dosya izinleri). '
            . 'Beklenen konum: ' . $displayPath . '. Duzeltme: dosya sahibine okuma izni verin '
            . '(chmod 600 ' . $displayPath . ').'
        );
    }
}
