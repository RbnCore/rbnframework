<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\System\Config\Definitions\DbProfiles;

/**
 * MasterDbData - Sistem (master) veritabanı kimlik profili (yalniz SABIT) 🧬🛰️
 *
 * RBN 3.5: Centralizes database requirements for the Master DB environment.
 *
 * [FW-DBPROFILES-TASIMA-95] Core/Services/Gatekeepers/Models ->
 * Core/System/Config/Definitions/DbProfiles. Bu sınıf kimlik/yapılandırma
 * verisidir, koruma mantığı DEĞİLDİR.
 *
 * [FW-TEK-SECRETS-DOSYASI-161] **GÖMÜLÜ BAĞLANTI DEĞERİ YOKTUR.**
 * Master DB'nin TÜM bağlantı değerleri (host, port, veritabanı adı, kullanıcı,
 * parola) ve SMTP ayarları `Core/System/Config/Secrets/secrets.php` TEK
 * dosyasından okunur. Okuyan metotlar `Engine/Config/` altındaki
 * `DbProfileResolver` / `SmtpProfileResolver` sınıflarındadır.
 *
 * [CONFIG-D-BARAN] PATRON KURALI: bu bir SABİT dosyasıdır — **içinde `function`
 * YOKTUR.** `KEYS_MAP` artık metot değil, LİTERAL `public const`; böylece
 * `Definition::get('database_master','KEYS_MAP')` mekanizması (sabit → metot
 * sırasıyla yoklar) çalışmaya devam eder ve bir profil eklendiğinde metot
 * yazmak zorunluluğu doğmaz.
 *
 * @see \Rbn\Framework\Core\System\Config\Engine\Database\DbProfileResolver
 * @see \Rbn\Framework\Core\System\Config\Engine\Database\SmtpProfileResolver
 */
final class MasterDbData
{
    /**
     * Explicit Definition Identity 🧬
     */
    // Sunucu Veritabani (Sistem Veritabani) bilgileri
    protected static ?string $definitionCategory = 'database_master';

    /** Ortam değişkeni / anahtar ön eki (master ortam değişkeni YOKTUR). */
    public const PREFIX = 'MASTER_DB';

    /** Bağlantı karakter kümesi (şema kimliği, sır DEĞİLDİR). */
    public const CHARSET = 'utf8mb4';

    /** Bu profilin ayar dosyası adı (anahtar keşfi `ENV_FILE` anahtarını sınıftan okur; `.env` varsayılanı). */
    public const ENV_FILE = '.env';

    /**
     * Master Database Required Tables (Identity & Control 🧬)
     *
     * [FW-TEK-SECRETS-DOSYASI-161] `DB_NAME` ve `SMTP_*` / `EMAIL_FROM_*`
     * sabitleri KALDIRILDI: değerler artık `secrets.php`'ten okunur.
     * Gerekli tablo listesi (şema) BURADA kalır - sır DEĞİLDİR, kodla sürümlenir.
     */
    public const REQUIRED_TABLES = [
        'developers',
        'ip_whitelist',
        'ip_blocks',
        'projects',
        'z_sys_heartbeats'
    ];

    /**
     * Anlamsal veritabanı anahtarı HARİTASI (sabit, metot değil) 🏺
     *
     * `Definition::get('database_master', 'KEYS_MAP')` bu sabiti döndürür
     * (`DefinitionResolver` sabitleri metottan ÖNCE arar). Değerler `PREFIX`
     * ile türetilir ama **literal** yazılmıştır: şema dosyası okunmadan da
     * doğrudan görülebilir, `getKeysMap()` metodu gerekmez.
     */
    public const KEYS_MAP = [
        'host' => 'MASTER_DB_HOST',
        'database' => 'MASTER_DB_NAME',
        'user' => 'MASTER_DB_USER',
        'password' => 'MASTER_DB_PASS',
        'charset' => 'MASTER_DB_CHARSET',
    ];
}
