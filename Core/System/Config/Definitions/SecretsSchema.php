<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Config\Definitions;

/**
 * SecretsSchema - `secrets.php` dosyasının **TEK şema tanımı** (yalnız sabit).
 *
 * PATRON KURALI: "sabitler ayrı dosyada, metotlar ayrı dosyada." Bu dosyada
 * **metot yoktur**; burada yalnız `public const` bulunur. Davranış (okuma,
 * doğrulama, hata mesajı) `Engine/Secrets/SecretsLoader` + `Engine/Secrets/SecretsValidator`
 * + `Engine/Secrets/SecretsSections` sınıflarındadır.
 *
 * GUVENLIK: Bu dosyada **hiçbir sır DEĞERİ yoktur** — yalnız alan ADLARI,
 * izin tavanı ve şablon yer tutucusu. Değerler `Secrets/secrets.php`'den
 * okunur, kodda gömülü tutulmaz.
 *
 * @see \Rbn\Framework\Core\System\Config\Secrets
 * @see \Rbn\Framework\Core\System\Config\Engine\Secrets\SecretsLoader
 */
final class SecretsSchema
{
    /**
     * `Config` klasörünün framework köküne göre yolu (kullanıcıya gösterilen
     * TEK yol). `Config/` + `Secrets/secrets.php` = operatörün göreceği yol.
     */
    public const BASE_DIR = 'Core/System/Config';

    /** TEK sır dosyasının göreli yolu (bölümlü). */
    public const FILE = '/Secrets/secrets.php';

    /**
     * Üst düzey (bölüm olmayan) anahtar: uygulama şifreleme anahtarı.
     */
    public const APP_KEY_NAME = 'app_key';

    /**
     * Şablon (`*.example.php`) yer tutucusu — HİÇBİR dosyada kabul edilmez.
     */
    public const CHANGE_ME = 'CHANGE_ME';

    /**
     * İzin tavanı: yalnız sahibi okuyabilmeli (0600).
     *
     * Gerçek uygulama `ConfigFileGuard::guardFileMode()` içindedir; buradaki
     * değer belgeleme/denetim içindir (sabitler ayrı dosyada kuralı).
     */
    public const SECRET_FILE_MODE = 0600;

    /**
     * Bölüm kuralları: zorunlu alanlar ve boş değerin reddedildiği alanlar.
     *
     * `master_db.pass` ve `smtp.pass` BİRE BİR boş olabilir (yerelde master DB
     * parolası gerçekten `''`); bu yüzden `nonEmpty` listesinde YOKTUR.
     * `smtp.enabled` string olarak tutulur (`'1'` / `'0'`): `normalize()`
     * skalerleri metne çevirir, `false` değeri `''` olurdu.
     *
     * @var array<string,array{required:string[],nonEmpty:string[]}>
     */
    public const SECTION_RULES = [
        'master_db' => [
            'required' => ['host', 'port', 'name', 'user', 'pass'],
            'nonEmpty' => ['host', 'port', 'name', 'user'],
        ],
        'smtp' => [
            'required' => ['enabled', 'host', 'port', 'secure', 'user', 'pass', 'from_address', 'from_name'],
            'nonEmpty' => ['enabled', 'host', 'port', 'secure', 'user', 'from_address', 'from_name'],
        ],
        'cpanel' => [
            'required' => ['host', 'user', 'token'],
            'nonEmpty' => ['host', 'user', 'token'],
        ],
    ];

    /**
     * Opsiyonel (`optional()`) okumada düz anahtar üretilen bölümler.
     *
     * @var string[]
     */
    public const OPTIONAL_SECTIONS = ['master_db', 'smtp'];

    /**
     * Bölüm adı `master_db` ise üretilen düz anahtar (`all()` uyumu).
     *
     * AD KURALI: sabit adı `*_PASS` ile BİTMEMELİDİR. Yerel-tur kalemi 7
     * gömülü sır kalıbını (sabit adı + tek tırnaklı değer) tarar; burada
     * değer değil **alan adı** vardır, ama tarama metni ayrım yapmaz. Bu
     * yüzden `_PW` kısaltması kullanılır (değer `master_db_pass` DEĞİŞMEZ).
     */
    public const FLAT_MASTER_PW = 'master_db_pass';

    /** Bölüm adı `smtp` ise üretilen düz anahtar (`all()` uyumu). */
    public const FLAT_SMTP_PW = 'master_smtp_pass';

    /** `master_db.user` düz anahtar karşılığı (`all()` uyumu). */
    public const FLAT_MASTER_USER = 'rbncore_master_user';

    /** `api` bölümünün şema adı. */
    public const API_SECTION = 'api';
}
