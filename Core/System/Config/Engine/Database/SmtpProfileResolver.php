<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Config\Engine\Database;

use Rbn\Framework\Core\System\Config\Secrets;

/**
 * SmtpProfileResolver - SMTP ve gönderen e-posta kimliğinin ÇÖZÜCÜSÜ ✉️📮
 *
 * [CONFIG-D-BARAN] PATRON KURALI: "sabitlerin olduğu dosyalarda METOT olmaz;
 * sabitler ayrı dosya, metotlar ayrı dosya." Bu metotlar
 * `Definitions/DbProfiles/MasterDbData.php` dosyasından BURAYA taşındı;
 * profil dosyası artık yalnız sabit içerir.
 *
 * SORUMLULUK (tek): `secrets.php` `smtp` bölümündeki 8 alanı okuyup
 * tipini düzeltmek (port → int, enabled → bool). Başka hiçbir konuyla
 * ilgilenmez: veritabanı bağlantısı `DbProfileResolver`'ın işidir.
 *
 * KAYNAK: **yalnız** `Secrets::smtp()` → `Core/System/Config/Secrets/secrets.php`
 * `smtp` bölümü. Ortam değişkeni yolu ve gömülü sabit YOKTUR
 * (FW-TEK-SECRETS-DOSYASI-161). Okuyucu fail-closed'dır: bölüm/alan eksikse
 * veya değer `CHANGE_ME` ise `RuntimeException`; sessiz varsayılan YOKTUR.
 *
 * @see \Rbn\Framework\Core\System\Config\Secrets::smtp()
 * @see \Rbn\Framework\Core\System\Config\Engine\Database\DbProfileResolver
 */
final class SmtpProfileResolver
{
    /** SMTP etkin mi? (`'1'` = açık). */
    public static function enabled(): bool
    {
        return Secrets::smtp()['enabled'] === '1';
    }

    /** SMTP sunucusu. */
    public static function host(): string
    {
        return Secrets::smtp()['host'];
    }

    /** SMTP portu. */
    public static function port(): int
    {
        return (int) Secrets::smtp()['port'];
    }

    /** SMTP şifreleme (`ssl` / `tls` / `''`). */
    public static function secure(): string
    {
        return Secrets::smtp()['secure'];
    }

    /** SMTP kullanıcı adı. */
    public static function user(): string
    {
        return Secrets::smtp()['user'];
    }

    /** SMTP parolası (fail-closed; boş OLABİLİR). */
    public static function pass(): string
    {
        return Secrets::smtp()['pass'];
    }

    /** Gönderen e-posta adresi. */
    public static function fromAddress(): string
    {
        return Secrets::smtp()['from_address'];
    }

    /** Gönderen görünen adı. */
    public static function fromName(): string
    {
        return Secrets::smtp()['from_name'];
    }
}
