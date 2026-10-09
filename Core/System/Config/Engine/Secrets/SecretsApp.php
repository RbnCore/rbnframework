<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Config\Engine\Secrets;

use Rbn\Framework\Core\System\Config\Definitions\SecretsSchema;

/**
 * SecretsApp - `secrets.php` **`app` bölümünün** okuma + tip doğrulama kurgusu.
 *
 * [FW-096-D8] Framework OS ortam değişkeni OKUMAZ; ortam bayrağı ve çalışma
 * ayarları `app` bölümündedir. SORUMLULUK (tek iş): bölümü şema
 * varsayılanlarıyla (`SecretsSchema::APP_DEFAULTS`) birleştirip tipini ve
 * izinli değerini (`APP_ALLOWED`) doğrulamak. Yol çözümü `SecretsLoader`,
 * okunabilirlik denetimi `SecretsValidator` içindedir. Dış yüz: `Secrets::app()`.
 *
 * GUVENLİK: uyarı metninde değer YAZILMAZ; yalnız alan adı.
 *
 * @see \Rbn\Framework\Core\System\Config\Secrets::app()
 */
final class SecretsApp
{
    /**
     * [FW-096-D8] `app` bölümü: şema varsayılanlarıyla birleşmiş, tipi
     * doğrulanmış çalışma ayarları.
     *
     * Bölümün TAMAMI opsiyoneldir. Dosya yok / bölüm yok / alan yok ise
     * `SecretsSchema::APP_DEFAULTS` içindeki GÜVENLİ varsayılan döner;
     * `environment` varsayılana düştüğünde (yok ya da geçersiz) süreç başına
     * bir kez loglanır. Tipi tutmayan alan da varsayılana düşer ve loglanır.
     *
     * DİKKAT: dosya VAR ama okunamaz/bozuk/izni geniş ise fail-closed hata
     * yukarı çıkar (güvenlik hatası "ayar yok" diye yutulmaz).
     *
     * @return array<string,bool|int|string>
     * @throws \RuntimeException Dosya var ama okunamaz/bozuk ise.
     */
    public static function read(): array
    {
        $values = [];
        if (!SecretsLoader::isFileMissing()) {
            SecretsValidator::readable(
                SecretsLoader::fullPath(),
                SecretsLoader::displayPath(),
                SecretsLoader::fileName()
            );
            $section = SecretsLoader::raw()[SecretsSchema::APP_SECTION] ?? [];
            $values = is_array($section) ? $section : [];
        }

        $out = [];
        foreach (SecretsSchema::APP_DEFAULTS as $key => $default) {
            $out[$key] = self::appValue($key, $values, $default);
        }

        return $out;
    }

    /** Tek `app` alanını tip + izinli değer listesine göre çözer. */
    private static function appValue(string $key, array $values, bool|int|string $default): bool|int|string
    {
        if (!array_key_exists($key, $values)) {
            if ($key === 'environment') {
                self::warnOnce($key, 'tanimli degil; ' . SecretsSchema::ENV_PRODUCTION . ' kabul edildi');
            }

            return $default;
        }

        $value = $values[$key];
        $typeOk = get_debug_type($value) === get_debug_type($default);
        if ($typeOk && is_string($value)) {
            $value = strtolower(trim($value));
            $typeOk = !isset(SecretsSchema::APP_ALLOWED[$key])
                || in_array($value, SecretsSchema::APP_ALLOWED[$key], true);
        }

        if (!$typeOk) {
            // Değer yazılmaz (sır olmasa da tek kural: hata metninde değer YOK).
            self::warnOnce($key, 'gecersiz (tip ' . get_debug_type($default) . ' bekleniyor ya da izinli deger degil); varsayilan kullanildi');

            return $default;
        }

        return $value;
    }

    /** @var array<string,true> Süreç başına bir kez yazılan `app` uyarıları. */
    private static array $warned = [];

    private static function warnOnce(string $key, string $reason): void
    {
        if (isset(self::$warned[$key])) {
            return;
        }
        self::$warned[$key] = true;

        error_log('RBN Uyari: ' . SecretsLoader::fileName() . ': ' . SecretsSchema::APP_SECTION
            . '.' . $key . ' ' . $reason . '. ' . SecretsLoader::hint(SecretsLoader::displayPath()));
    }

    /** `app` uyarı bayraklarını sıfırlar (`Secrets::resetCache()` ile birlikte). */
    public static function resetWarnings(): void
    {
        self::$warned = [];
    }

}
