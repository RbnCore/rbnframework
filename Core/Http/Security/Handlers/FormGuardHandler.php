<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Http\Security\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\Support\Bridges\Helpers\Library\LogThrottle;

/**
 * FormGuardHandler - The Master Form Security Orchestrator 🛡️⚖️
 * 
 * RBN 3.5 "Masterpiece": Atomic actor responsible for running
 * multi-layered security protocols on form submissions.
 */
class FormGuardHandler extends BaseComponent
{
    /**
     * [GUVENLIK YAMASI 2026-10-01] Kademeli (progressive) global IP cezasi.
     * Onceki hali HER ihlalde `10080` dakika (7 GUN) global ban atiyordu: tek bir
     * honeypot alanina yanlislikla yazan (veya botun yazdigi) kullanici 7 gun
     * siteye erisemiyor, ayni IP'deki mesru ziyaretciler (ofis/NAT/otel Wi-Fi/
     * CGNAT) de birlikte engelleniyordu = kendi kendine DoS (52 raporu, ORTA-1).
     * Kademeler: ilk ihlal 15 dk, ikinci 1 saat, sonrakiler 24 saat; hicbirinde
     * 7 GUN yok. Kademe mevcut `master.ipBlock` kayit sayisindan turetilir.
     */
    private const KADEMELI_CEZA_DAKIKA = [15, 60, 1440, 1440];

    /* ==========================================================================
       [F-10 / F-14 · 2026-10-04 · zeki-6eb7f5] LOG-ONLY SAYAÇLAR (GÖZLEM)
       ==========================================================================
       AMAÇ: "ölçmeden aç/kapat" kuralını mümkün kılmak. Bu sayaçlar **hiçbir
       kararı değiştirmez**: reddetmez, geçirmez, hız sınırı uygulamaz, ban
       yazmaz. Yalnız `security` kanalına ÖZETLENMİŞ sayaç yazar.

       F-10: `options` vermeyen `form()` çağrıları → hız sınırı UYGULANMAZ
             (`rateLimitEnabled` false). Kasıtlı varsayılan; ama hangi formların
             limitsiz olduğu hiçbir yerde görünmüyordu.
       F-14: Origin **ve** Referer yok olan istekler (`OriginHandler`, aynı
             katmandaki kardeş sınıf) → API dostu geçiş; sayısı görünmüyordu.

       GÜRÜLTÜ SINIRI: süreç (FPM worker) belleğinde biriktirilir ve yalnız
       (a) gün değişiminde önceki günün özeti, (b) 1/10/100/1000 basamak
       eşiklerinde yazılır. Yani bir günde kategori başına EN ÇOK 5 satır.
       Sayaç **günlük** anahtarlanır: uzun ömürlü süreçte ertesi gün eski
       günün toplamı "gün başı özeti" olarak bir kez daha yazılır ve sıfırlanır.

       TEK DEPO: iki handler da bu statik sayacı kullanır (tek doğruluk
       kaynağı; iki ayrı sayaç = iki ayrı sayı).
       ========================================================================== */

    /** Sayaç anahtarı: hız sınırı açılmamış form gönderimi (F-10). */
    public const SAYAC_SINIRSIZ_FORM = 'form_without_rate_limit';

    /** Sayaç anahtarı: Origin ve Referer başlığı yok (F-14). */
    public const SAYAC_ORIJIN_YOK = 'origin_referer_missing';

    /**
     * LOG-ONLY sayaç satırının throttle anahtarı etiketi (sayaç adı eklenir).
     *
     * Sayaç DEĞERİ her zaman artar (kümülatif ölçüm); yalnız YAZILAN satır bu
     * saatlik kapıdan geçer. Kapı olmadan süreç içi eşik listesi
     * (1/10/100/1000) PHP-FPM'de her istekte "1"e döndüğü için bu gözlem
     * her istekte bir satır üretiyordu (ölçüm: duman koşusunda 72 satır).
     *
     * @see \Rbn\Framework\Core\Support\Bridges\Helpers\Library\LogThrottle
     */
    public const LOG_ONLY_SAYAC_THROTTLE_TAG = 'f10-f14-log-only-sayac';

    /**
     * Sayaç anahtarı: GERİYE UYUMLU (deprecated) GET çıkış rotası (R-16).
     * POST+CSRF yolu eklenildi; bu yol yalnız geriye uyum için duruyor.
     */
    public const SAYAC_GET_CIKIS = 'logout_get_deprecated';

    /** @var array<string,array{gun:string,adet:int}> Süreç içi sayaç deposu. */
    private static array $logOnlySayaclar = [];

    /**
     * LOG-ONLY sayacı bir artırır; eşik/gün eşleşmesinde `security` kanalına
     * yazar. HATA YUTULUR: gözlem katmanı kararı bozamaz (fail-open gözlem).
     *
     * @param string $anahtar `self::SAYAC_*` sabitlerinden biri.
     */
    public function artirLogOnlySayac(string $anahtar): void
    {
        $bugun = date('Y-m-d');

        $kayit = self::$logOnlySayaclar[$anahtar] ?? ['gun' => $bugun, 'adet' => 0];

        // Gün değiştiyse: önceki günün ÖZETİNİ bir kez yaz, sıfırla.
        if ($kayit['gun'] !== $bugun) {
            $this->securityLog('LOG_ONLY_SAYAC_GUN_SONU', $anahtar, [
                'onceki_gun' => $kayit['gun'],
                'toplam'     => $kayit['adet'],
            ]);
            $kayit = ['gun' => $bugun, 'adet' => 0];
        }

        $kayit['adet']++;
        self::$logOnlySayaclar[$anahtar] = $kayit;

        // Basamak eşikleri: gün içinde kategori başına en çok 4 satır + gün sonu.
        if (in_array($kayit['adet'], [1, 10, 100, 1000], true)
            && LogThrottle::once(self::LOG_ONLY_SAYAC_THROTTLE_TAG . ':' . $anahtar)
        ) {
            $this->securityLog('LOG_ONLY_SAYAC', $anahtar, [
                'gun'      => $bugun,
                'adet'     => $kayit['adet'],
                'davranis' => 'log-only (karar degismedi)',
            ]);
        }
    }

    /**
     * LOG-ONLY sayaç okuma (ölçüm/test arayüzü). Anahtar verilmezse tümü.
     *
     * @return int|array<string,int>
     */
    public static function logOnlySayacOku(?string $anahtar = null): int|array
    {
        if ($anahtar !== null) {
            return (int) (self::$logOnlySayaclar[$anahtar]['adet'] ?? 0);
        }

        $hepsi = [];
        foreach (self::$logOnlySayaclar as $k => $v) {
            $hepsi[$k] = (int) $v['adet'];
        }

        return $hepsi;
    }

    /** Sayaç satırını `security` kanalına yazar; yazılamazsa sessizce geçer. */
    private function securityLog(string $mesaj, string $anahtar, array $baglam): void
    {
        try {
            $this->logs()->channel('security')->notice($mesaj, $baglam + ['sayac' => $anahtar]);
        } catch (\Throwable $e) {
            // Gözlem katmanı asla kararı bozamaz.
        }
    }

    /**
     * Executes the global security audit protocol for a form submission.
     */
    public function audit(array $data, array $options = []): array
    {
        // 0. High-Level Admin & Developer Bypass (Centralized Shield Rules 👮‍♂️)
        // [FW-F08 · 2026-10-03 · zeki-6eb7f5] Bu muafiyet ARTIK CSRF'i KAPSAMAZ.
        // Onceki halde erken `return ['success' => true]` 6. adimdaki CSRF kontrolune
        // hic ugramiyordu; yani `developer`/`admin`/`superadmin`/master-developer
        // oturumlari CSRF dogrulamasini tamamen atliyordu (F-08).
        // Simdi: bot/origin/user-agent/rate-limit korumalari bu roller icin
        // atlanmaya DEVAM eder, ama CSRF **herkes icin** kosar (asagida 6.).
        $privileged = false;
        try {
            $role = $this->storage->sessions()->get('user_role');
            $isMaster = $this->storage->sessions()->get('is_master_developer');

            $privileged = ($isMaster === true
                || in_array($role, \Rbn\Framework\Bundles\RbnSuite\RbnAuth\Models\AuthRole::BYPASS_ROLES, true));
        } catch (\Throwable $e) {
            // [F-09 · DÜZELTME] FAIL-CLOSED: oturum/depolama okunamıyorsa
            // muafiyet **verilmez**.
            //
            // ÖNCEKİ HALİ: catch bloğu rol listesini ELLE TEKRAR yazıyor ve
            // `$privileged`'i hesaplıyordu. Yani depolama katmanı bir hata
            // verdiğinde denetimler ATLANIYORDU — hata durumu güvenliği
            // artırmak yerine azaltıyordu. İstisna zaten muafiyet için bir
            // gerekçe DEĞİLDİR.
            $privileged = false;
            error_log('RBN Guvenlik: formGuard adim 0 oturum okunamadigi icin muafiyet UYGULANMADI: ' . get_class($e));
        }

        if ($privileged) {
            // [F-10 · LOG-ONLY] Muafiyetli (admin/developer) oturumlar hız
            // sınırını KASTEN atlar; bu da ölçülür (ayrı sayaç değil, aynı
            // "limitsiz form" sayacı — açma kararı önce buradan görülür).
            if (!($options['rateLimitEnabled'] ?? false) && ($options['logOnlySayac'] ?? true)) {
                $this->artirLogOnlySayac(self::SAYAC_SINIRSIZ_FORM);
            }

            // CSRF fail-closed: token yoksa/yanlissa/suresi dolmussa reddet.
            if ($options['csrf'] ?? true) {
                $csrfResult = $this->handler('csrf')->verify($data);
                if (!$csrfResult['success']) {
                    return ['success' => false, 'message' => $csrfResult['message'] ?? 'CSRF Token Geçersiz.'];
                }
            }

            return ['success' => true];
        }

        // [F-10 · 2026-10-04 · zeki-6eb7f5] LOG-ONLY GÖZLEM: hız sınırı
        // uygulanmayan form gönderimi. KARAR DEĞİŞMİYOR — yalnız sayılır.
        // Ölçmeden varsayılan hız sınırı açılamaz; bu sayaç o ölçümün kaynağı.
        if (!($options['rateLimitEnabled'] ?? false) && ($options['logOnlySayac'] ?? true)) {
            $this->artirLogOnlySayac(self::SAYAC_SINIRSIZ_FORM);
        }

        $ip = $this->request->ip();

        // 1. Auto-Trim (Always Clean first 🛡️)
        if ($options['auto_trim'] ?? true) {
            $data = $this->handler('sanitization')->trimData($data);
        }

        // 1b. [F-24] HTML etiketi temizligi — **ISTEGE BAGLI, varsayilan KAPALI**
        //
        // ÖLÇÜLEN BOŞLUK: "Sanitization" adımı yalnız `trim()` yapıyordu;
        // `stripTags()` hiçbir yerden çağrılmıyordu (çıktı tarafı kaçışına
        // tam güveniliyordu).
        //
        // BİLİNÇLİ KARAR: temizlik varsayılan adım yapılmadı. Form gövdesinin
        // TAMAMı taranıyor; `strip_tags` zengin metin alanlarını (panel açıklama,
        // içerik gövdesi) sessizce boşaltırdı. Bunun yerine **proje/form
        // seviyesinde açılan** bir bayrak eklendi:
        //   ['strip_tags' => true]              → tüm alanlar
        //   ['strip_tags' => ['konu','mesaj']]  → yalnız bu alanlar
        $stripFields = $options['strip_tags'] ?? false;
        if ($stripFields !== false && $stripFields !== null) {
            $data = $this->handler('sanitization')->stripTags(
                $data,
                is_array($stripFields) ? $stripFields : []
            );
        }

        // 2. Origin & Referer Check (The Physical Gate ⛵)
        if ($options['origin_check'] ?? true) {
            $allowed = $options['allowed_origins'] ?? [];
            $strict = $options['origin_strict'] ?? false;

            if (!$this->handler('origin')->validate($allowed, $strict)) {
                return ['success' => false, 'message' => 'Güvenlik ihlali (Origin). İstek kaynağı doğrulanamadı.'];
            }
        }

        // 3. Bot (Honeypot) Check
        if ($options['honeypot'] ?? true) {
            $hpField = ($options['honeypot_dynamic'] ?? false)
                ? $this->handler('bot')->getDynamicName()
                : ($options['honeypot_field'] ?? 'website');

            if (!$this->handler('bot')->validateHoneypot($data, $hpField)) {
                // [GUVENLIK] Kademeli ceza: artik 7 gunluk global ban degil (ORTA-1).
                // [B71-#5] Tek dogruluk kaynagi: kademe `applyGlobalBlock` ICINDE
                // hesaplanir; cagiran kademeyi GECIRMEZ, uygulanan degeri dondurur.
                // Onceki hali `kademeliCezaDakika()` + `applyGlobalBlock(...,$dakika)`
                // idi = kademe iki yerden birden okunuyordu (ikinci yol ikisinin
                // birinin unutulmasina acik).
                $dakika = $this->applyGlobalBlock($ip, 'Honeybot: Otomatik Bot Tespiti (kademeli ceza)');

                return [
                    'success' => false,
                    'message' => $dakika > 60
                        ? 'Güvenlik ihlali (Bot). Form gönderiminiz güvenlik nedeniyle engellendi.'
                        : 'Güvenlik ihlali (Bot). Lütfen formu birazdan tekrar gönderin.',
                ];
            }
        }

        // 4. Bot (User-Agent) Check
        if ($options['check_ua'] ?? true) {
            if (!$this->handler('bot')->validateUserAgent()) {
                $this->applyGlobalBlock($ip, 'Suspicious User-Agent Detected (kademeli ceza)');
                return ['success' => false, 'message' => 'Güvenlik ihlali (Header). Form gönderiminiz güvenlik nedeniyle engellendi.'];
            }
        }

        // 5. Rate Limit Check (Through IpGuardService)
        //
        // [F-04 · 2026-10-03 · zeki-6eb7f5] BURADA YALNIZCA "zaten bloklu mu?"
        // kontrolu kalir; sayaci ARTIRAN `recordHit()` 6b'ye (CSRF SONRASI) tasindi.
        // Onceki halde token'i olmayan capraz-site POST'lar da sayaci tuketiyor,
        // saldirgan kurbanin IP'sinden girisini kilitleyebiliyordu (DOS). Sayacin
        // ANLAMI degismedi: gecerli token'li BASARISIZ denemeler yine sayilir (A0-3).
        $rateLimitAction = ($options['rateLimitEnabled'] ?? false)
            ? ($options['action'] ?? 'default_form')
            : null;

        if ($rateLimitAction !== null) {
            /** @var \Rbn\Framework\Core\Services\Gatekeepers\IpGuardService $ipGuard */
            $ipGuard = $this->service('ipGuard');

            if ($ipGuard && $ipGuard->isLimitReached($ip, $rateLimitAction)) {
                $remTime = $ipGuard->remainingTime($ip, $rateLimitAction);
                return ['success' => false, 'message' => "Çok fazla başarısız deneme yaptınız. Lütfen {$remTime} dakika sonra tekrar deneyin."];
            }
        }

        // 6. CSRF Check
        if ($options['csrf'] ?? true) {
            $csrfResult = $this->handler('csrf')->verify($data);
            if (!$csrfResult['success']) {
                return ['success' => false, 'message' => $csrfResult['message'] ?? 'CSRF Token Geçersiz.'];
            }
        }

        // 6b. [F-04] Sayac artirimi — CSRF GECTIKTEN SONRA.
        if ($rateLimitAction !== null) {
            /** @var \Rbn\Framework\Core\Services\Gatekeepers\IpGuardService $ipGuard */
            $ipGuard = $this->service('ipGuard');
            $ipGuard?->recordHit($ip, $rateLimitAction);
        }

        // 7. Injection (XSS) Check
        if ($options['xss_protection'] ?? true) {
            if (!$this->handler('injection')->detectXss($data)) {
                $this->applyGlobalBlock($ip, 'XSS Attack Attempt (kademeli ceza)');
                return ['success' => false, 'message' => 'Güvenlik ihlali (XSS). İşlem durduruldu.'];
            }
        }

        // 8. File Security (Upload Sentinel 📦)
        if ($options['file_security'] ?? true) {
            $files = $this->request->allFiles();
            if (!empty($files)) {
                $fileResult = $this->handler('fileSecurity')->validateRequestFiles($files);
                if (!$fileResult['success']) {
                    $this->applyGlobalBlock($ip, ($fileResult['reason'] ?? 'File Upload Security Violation') . ' (kademeli ceza)');
                    return ['success' => false, 'message' => 'Güvenlik ihlali (Dosya). İşlem durduruldu.'];
                }
            }
        }

        return ['success' => true, 'data' => $data];
    }

    /**
     * [GUVENLIK] Kademeli ceza suresini hesaplar.
     *
     * Kademe, o IP'nin `master.ipBlock` tablosundaki **AKTIF** kayit sayisindan
     * turetilir:
     *   0 aktif kayit  -> 1. ihlal  -> 15 dakika
     *   1 aktif kayit  -> 2. ihlal  -> 60 dakika (1 saat)
     *   2+ aktif kayit -> 3. ihlal+ -> 1440 dakika (24 saat)
     *
     * [GUVENLIK YAMASI 2026-10-01 · gorev 94] `blocked_until` filtresi eklendi.
     * Onceki hali `where('ip_address','=',$ip)->count()` idi: SURESI DOLMUS kayitlari
     * da sayiyordu, bu yuzden saya hic DUSMUYORDU — kanitli deney (Banu-71 #4):
     * 4 kayit, hepsi suresi dolmus, AKTIF yasak 0 (kullanici 25 saattir serbest)
     * idi; yeni ihlal sayaci 4 gordu -> 1440 dk, yani "kademeli ceza" duz bir
     * kalici ban demekti.
     *
     * Artik yalnizca `blocked_until > now()` olan kayitlar sayilir. `now()` RBN
     * helper'idir ve BIR BIND parametre olarak gider (SQL NOW() DEGIL) — yani
     * `blockIp()` ile yazilan degerle ayni saat tabanini kullanir:
     *   - Serbest kalan kullanici 15 dakikadan baslar (kademe dustu).
     *   - Halen yasakliyken yeni ihlal ustel kademede kalir.
     * DB okunamazsa EN YUMUSAK kademe (15 dk) secilir; boylece hata durumunda
     * kullanici 7 gun kilitlenmez ("mesru kullaniciyi kilitlememek").
     *
     * [DOGRULANDI · FW-B71-4-GEOIP-BARAN] Bu filtre metin taramasiyla degil,
     * METOT CALISTIRILARAK olculdu (5 senaryo, master DB, RFC5737 IP'leri,
     * gecici kayitlar `finally` ile silindi): 4 dolmus + 0 aktif -> 15 dk
     * (eski hâli 1440 dk verirdi), +1 aktif -> 60 dk, +2 aktif -> 1440 dk,
     * 0 kayit -> 15 dk, 5 aktif -> 1440 dk (tavan `min($aktif, count-1)`).
     * B71-71-kosullari.php #4 de ayni sarti tarar ve YESIL'dir.
     *
     * [BILINEN ACIK NOKTA · EMIN DEGILIM] Sayac `master.ipBlock` tablosunun
     * TAMAMINI sayar; `blockIp()` kaydi `project_id`'li yazdigindan, ayni IP'nin
     * BASKA bir projedeki aktif kaydi da bu kademeyi yukseltir. "Global" ceza
     * icin muhtemelen dogru, ama bilincli olarak degistirilmedi.
     */
    private function kademeliCezaDakika(string $ip): int
    {
        try {
            $aktif = (int) $this->model('master.ipBlock')
                ->where('ip_address', '=', $ip)
                ->where('blocked_until', '>', now())
                ->count();
            $kademe = min($aktif, count(self::KADEMELI_CEZA_DAKIKA) - 1);
        } catch (\Throwable $e) {
            $kademe = 0;
        }

        return self::KADEMELI_CEZA_DAKIKA[max(0, $kademe)];
    }

    /**
     * Helper to apply a ban via IpBlockModel (PROJE KAPSAMLI).
     *
     * [FW-IP-ENFORCE-HAZIRLIK · Ö-3] Metot adi tarihsel nedenle
     * `applyGlobalBlock` kaldi (cagiranlar ve testler bu adi kullaniyor);
     * ANLAM degisti: ceza artik bulunulan PROJENIN kapsaminda yazilir.
     * KOSUL: proje kimligi cozulemezse kayit 0 (global) olarak yazilir.
     *
     * [GUVENLIK] $durationMinutes varsayilani artik 10080 (7 GUN) DEGILDIR; varsayilan
     * KADEMELI cezadir (15 dk / 1 saat / 24 saat), Boylece "yanlislikla 7 gun kalici
     * ban" mumkun degildir (ORTA-1). Gerekirse cagiran bilerek sureyi gecerek sert
     * sinir uygulayabilir.
     *
     * [B71-#5] Donus degeri: KADEMELI CEZA DA KADAR UYGULANDI (dakika). Boylece
     * kademenin TEK hesaplandigi yer burasi olur; cagiranlar
     * `kademeliCezaDakika()`'yi dogrudan cagirmaz (cift yol kapanir).
     *
     * @return int Uygulanan sure (dakika).
     */
    private function applyGlobalBlock(string $ip, string $reason, ?int $durationMinutes = null): int
    {
        // [FW-IPKATMAN-ON · 2026-10-03 · zeki-6eb7f5] LOOPBACK MUAFIYETI.
        //
        // KOK NEDEN (FW-KIMLIK-B A-19'da olculdu): bu metod `curl/...` gibi
        // gercekci olmayan User-Agent'larla gelen **yerel** POST'larda calisiyordu
        // ve master `ip_blocks` tablosuna ban satiri yaziyordu. Test/duman
        // trafigi (curl) ve ayni sunucudan gelen saglik/cron istekleri bu yuzden
        // KENDI kendini banliyordu: IP katmani `log_only` oldugu icin bugun
        // engellemiyor, ama `enforce` acilirsa yerelden TEK giris gecemez.
        //
        // KAPSAM (brif kilit kisiti): muafiyet YALNIZ `127.0.0.0/8` + `::1` +
        // IPv4-eslemeli karsiliklari. RFC1918 ozel aglar (192.168.x / 10.x) ve
        // link-local/ULA **MUAF DEGILDIR** — ofis ici ag gercek istemci olabilir;
        // onlari muaf saymak gercek saldirida korumayi kapatirdi.
        //
        // `PreBoot::isLocalClientAddress()` bilerek KULLANILMAZ: o, ozel ag +
        // ULA + link-local'in hepsini "yerel" sayar (gelistirme ortami kapisi
        // icindir). Buradaki ihtiyac loopback-ONLY'dir.
        //
        // MOD MANTIGI DEGISMEDI: `log_only`/`enforce` ayrimi, kademe hesabi ve
        // `blockIp()` cagrisi oldugu gibi durur; yalniz kaydin YAZILMAMASI
        // kisa devre yapar. KademeliCezaDakika'ya da GIRILMEZ (gereksiz DB okuması).
        //
        // DONUS DEGERI: kayit yazilmadigi icin **0 dakika**. Cagiranlar bunu
        // yalniz mesaj tonu icin kullanir (`$dakika > 60`); 0 "kisa sureli uyari"
        // dalina duser, yani istemciye "birazdan tekrar gonderin" denir — dogru.
        if (\Rbn\Framework\Core\System\Kernel\Base\PreBoot::isLoopbackAddress($ip)) {
            return 0;
        }

        $countryCode = $this->service('ipGuard')->countryCode($ip);
        $dakika = $durationMinutes ?? $this->kademeliCezaDakika($ip);

        // [FW-IP-ENFORCE-HAZIRLIK · Ö-3 · 2026-10-03 · zeki-6eb7f5] CEZA KAPSAMI.
        //
        // KOK NEDEN (B-2): ceza `project_id = 0` (GLOBAL) yaziliyordu.
        // `IpGuardHandler` ban sorgusu `project_id = 0 VEYA = <aktif proje>`
        // oldugu icin sonuc: **ayni NAT IP'sindeki butun projeler** bu IP'yi
        // yasakli goruyordu — yani saldirgan degil KURBAN (ofis/CI/hotspot
        // arkadasi) banlaniyordu. Tek bir honeypot alanina yanlislikla yazan
        // insan, kendi sirketinin diger sitelerine de giremiyordu.
        //
        // DUZELTME: ceza bulunulan/aktif PROJENIN kapsaminda yazilir.
        // Ban satiri SEMA DEGISTIRILMEZ — mevcut `project_id` kolonu kullanilir
        // (kolon ekleme YOK). `IpGuardHandler::isBlocked` sorgusu zaten
        // `global VEYA proje` kapsamini taradigi icin proje-kapsamli ban yalniz
        // O projede uygulanir; 0 yazan ESKI (global) kayitlar yine global
        // gecerlidir (geri uyumluluk bozulmaz).
        //
        // Kademeli sure hesabi (`kademeliCezaDakika`) DEGISMEDI.
        $projeId = function_exists('project_id') ? (int) project_id() : 0;

        /** @var \Rbn\Framework\Core\Database\Repositories\Master\IpGuardRepository $ipGuardRepo */
        $ipGuardRepo = $this->repository('master.ipGuard');
        // Proje kimligi cozulemezse 0'a duser: o zaman ban GLOBAL yazilir
        // (fail-closed: proje bilinmiyorsa ceza kapsamini genis tutmak eski
        // davranistir; tesadüfi sessiz daraltma olmaz).
        $ipGuardRepo->blockIp($ip, $reason, $dakika, $projeId, $countryCode); // Kademeli, proje kapsamli

        return $dakika;
    }
}
