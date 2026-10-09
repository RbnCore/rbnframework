<?php
declare(strict_types=1);

/**
 * secrets.example.php - TEK SIR DOSYASININ SABLONU (bu dosya depoya girer)
 *
 * KULLANIM (3 adim, baska dosya YOK):
 *   1) Bu dosyayi ayni klasorde `secrets.php` adiyla kopyalayin.
 *   2) Asagidaki `CHANGE_ME` degerlerini gercek degerlerinizle degistirin.
 *   3) Izinleri kisitin: `chmod 600 secrets.php`
 *
 * `secrets.php` `.gitignore`'ludur; ASLA commit edilmez.
 * Bu sablon YALNIZCA alan adlarini ve ornek yazim gosterir - HICBIR gercek
 * deger icermez.
 *
 * OKUMA: \Rbn\Framework\Core\System\Config\Secrets (TEK okuyucu) - fail-closed.
 *   Secrets::masterDb() / ::smtp() / ::cpanel() / ::api('iyzico') / ::appKey() / ::app()
 *
 * ORTAM DEGISKENI YOK: framework isletim sistemi ortam degiskeni okumaz.
 * Ortam bayragi dahil butun anahtarlar BU dosyadadir (`app` bolumu).
 */

return [

    // ---------------------------------------------------------------------
    // master_db - Master (sistem) veritabani baglantisi
    // Okuyan: DbProfileResolver::host()/port()/databaseName()/user()/password()
    // ---------------------------------------------------------------------
    'master_db' => [
        // Veritabaninin calistigi sunucu. Yerelde genelde `127.0.0.1`,
        // uzak kurulumda MySQL sunucusunun IP'si veya alan adi.
        'host' => 'CHANGE_ME', // ornek: 127.0.0.1

        // Veritabani portu. MySQL varsayilan: 3306
        'port' => 'CHANGE_ME', // ornek: 3306

        // Master veritabaninin ADI (ornegin `rbncore_master`).
        'name' => 'CHANGE_ME', // ornek: rbncore_master

        // MySQL KULLANICI ADI. Guvenlik icin `root` degil, en az yetkili
        // bir hesap kullanin. Bos birakilamaz.
        'user' => 'CHANGE_ME', // ornek: rbncore_master

        // MySQL PAROLASI. Gercekten bos bir parola gerekliyse degeri acikca
        // bos string olarak yazin:  'pass' => '',
        // (Bos olabilir; `CHANGE_ME` KABUL EDILMEZ.)
        'pass' => 'CHANGE_ME', // ornek: (parolaniz)
    ],

    // ---------------------------------------------------------------------
    // smtp - Sistem e-posta gonderimi
    // Okuyan: SmtpProfileResolver::enabled()/host()/port()/secure()/
    //         user()/pass()/fromAddress()/fromName()
    // ---------------------------------------------------------------------
    'smtp' => [
        // E-posta gonderimi acik mi?  '1' = acik,  '0' = kapali
        'enabled' => 'CHANGE_ME', // ornek: 1

        // SMTP sunucusu (ornegin mail.sirketiniz.com).
        'host' => 'CHANGE_ME', // ornek: mail.sirketiniz.com

        // SMTP portu. SSL icin genelde 465, STARTTLS icin 587.
        'port' => 'CHANGE_ME', // ornek: 465

        // Sifreleme turu:  'ssl'  veya  'tls'
        'secure' => 'CHANGE_ME', // ornek: ssl

        // SMTP hesap kullanici adi (genelde e-posta adresi).
        'user' => 'CHANGE_ME', // ornek: info@sirketiniz.com

        // SMTP hesap PAROLASI. Gercekten bos bir parola gerekliyse acikca
        // bos string yazin. (Bos olabilir; `CHANGE_ME` KABUL EDILMEZ.)
        'pass' => 'CHANGE_ME', // ornek: (parolaniz)

        // Gonderenin e-posta adresi.
        'from_address' => 'CHANGE_ME', // ornek: info@sirketiniz.com

        // Gonderenin gorunen adi (e-postada karsidaki isim).
        'from_name' => 'CHANGE_ME', // ornek: Sirketiniz
    ],

    // ---------------------------------------------------------------------
    // cpanel - cPanel yonetim paneli kimlik bilgileri
    // Okuyan: CPanelProvider (CPanelService::host())
    // TEK cagiran kapisi: CPanelProvider. Port kodda sabittir (2083/2096).
    // ---------------------------------------------------------------------
    'cpanel' => [
        // Sunucu adi. Port EKLEMEYIN (kod kendisi ekler).
        'host' => 'CHANGE_ME', // ornek: www.sirketiniz.com

        // cPanel kullanici adi.
        'user' => 'CHANGE_ME', // ornek: sirketiniz

        // cPanel parolasi veya API token'i.
        'token' => 'CHANGE_ME', // ornek: (token'iniz)
    ],

    // ---------------------------------------------------------------------
    // app_key - Uygulama sifreleme anahtari (ust duzey, bolum DEGIL)
    // Okuyan: CryptoHelper (encrypt/decrypt/blindIndex).
    // Bos OLAMAZ ve gomulu/sabit tuz KULLANILMAZ. Guclu ve rastgele bir
    // deger uretin, ornek:
    //   php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
    // ---------------------------------------------------------------------
    'app_key' => 'CHANGE_ME', // ornek: 64 karakterlik onaltilik metin

    // ---------------------------------------------------------------------
    // app - Ortam bayragi ve calisma ayarlari (sir DEGIL; bolumun TAMAMI opsiyonel)
    // Okuyan: Secrets::app()  (sema: Definitions/SecretsSchema.php APP_DEFAULTS)
    // Deger tipi PHP tipidir: true/false, tam sayi, metin. Tipi tutmayan ya da
    // izinli olmayan deger GUVENLI varsayilana duser ve loglanir.
    // Yazilmayan alan asagidaki varsayilani alir.
    // ---------------------------------------------------------------------
    'app' => [
        // Calisma ortami: 'production' (canli) | 'development' (yerel makine).
        // YAZILMAZSA ya da CHANGE_ME kalirsa 'production' kabul edilir ve loglanir.
        // Okuyan: PreBoot::isProductionDeclared()
        'environment' => 'CHANGE_ME', // ornek: production

        // Operatorun acik hata ayiklama kapisi. true = uretimde bile debug ACIK.
        // Varsayilan false. Okuyan: PreBoot::detectEnvironment()
        'debug' => false,
        'dev' => false,

        // Koruma katmani hata verirse istegi ENGELLE (true, guvenli) mi gecir (false) mi.
        // Okuyan: SystemGuardHandler::resolveFailClosed()
        'guard_failclosed' => true,

        // Tanilama loglarinin saatlik kisiti. false = kisit yok (yalniz olcum/test).
        // Okuyan: LogThrottle
        'log_throttle' => true,

        // Proje DB profili: '' (otomatik) | 'local' | 'production'. Sunucu cron'u icin acik karar.
        // Okuyan: ProjectDbProfileResolver::activeProfile()
        // 'db_profile' => 'CHANGE_ME', // ornek: production

        // Eski gomulu tuzla sifrelenmis veriyi okumak icin GECICI onay (varsayilan false).
        // true ise ust duzey 'legacy_salt' da tanimli olmalidir. Okuyan: CryptoHelper
        'allow_legacy_salt' => false,

        // Yalniz TEST: sroweb Telegram test router'inda mesaj basina gecikme (ms).
        // 'tg_send_delay_ms' => 0,
    ],

    // Eski gomulu tuz (legacy, SIR). Yalniz app.allow_legacy_salt = true iken okunur.
    // 'legacy_salt' => 'CHANGE_ME',

    // ---------------------------------------------------------------------
    // api - Harici servis API kimlik bilgileri (odeme, SMS, e-posta saglayici)
    // Okuyan: Secrets::api('<hizmet>')
    // Yeni dis servis eklemek icin `api` altina yeni bir anahtar + satir
    // eklemek YETERLIDIR; ikinci bir sır dosyasi acmaya GEREK YOKTUR.
    // Her satirin tum anahtarlari zorunludur ve bos olamaz.
    // ---------------------------------------------------------------------
    'api' => [
        // 'iyzico' => [
        //     'api_key'    => 'CHANGE_ME',
        //     'secret_key' => 'CHANGE_ME',
        // ],
    ],
];
