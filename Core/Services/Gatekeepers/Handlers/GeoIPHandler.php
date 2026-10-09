<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Gatekeepers\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\Database\Repositories\Common\ShieldSettingsRepository;
use Rbn\Framework\Core\Support\Bridges\Helpers\Library\LogThrottle;
use Rbn\Framework\Core\System\Paths\Paths;

/**
 * GeoIPHandler - The Location Detective 🌍🛰️
 * 
 * RBN Framework: Atomic actor for IP-based geolocation and flag support.
 */
class GeoIPHandler extends BaseComponent
{
    /**
     * Bilinmeyen / hata durumu ulke kodu. Hata yolunda DAIMA bu deger doner:
     * istek ASLA durdurulmaz (fail-closed yonu burada YANLIS olurdu), sadece
     * ulke bilgisi atlanir.
     */
    public const BILINMEYEN_ULKE = 'XX';

    /**
     * [G-08 KAPANDI — FW-GEOIP-IPWHO-146] GeoIP saglayicisi ve SEKI.
     *
     * KARAR VERİLDİ: ipwho.is (team member 144, otopilot; patron sabah onayı:
     * değişiklik tek satır)
     *
     * `{ip}` yerine sorgulanacak IP yazilir. Sorgu artik **TLS'li** calisir
     * (`https://`) = G-08 "duz HTTP" aciigi kapandi.
     *
     * NEDEN ip-api.com DEGIL:
     *   Saglayicinin kendi dokumani diyor ki: "SSL (HTTPS) — 256-bit SSL
     *   encryption is NOT available for this free API. Please see our pro
     *   service." Yani ip-api.com'in UCRETSIZ plani TLS desteklemiyor; duz
     *   HTTP'den cikmanin tek yolu PRO (ucretli) plana gecmek.
     *
     * NEDEN ipwho.is:
     *   - Ucretsiz planda HTTPS ACIK (team member 144: gercek istek, 8.8.8.8 -> 200),
     *   - anahtarsiz, ticari kullanim izinli,
     *   - kotasi 1000 istek/gun/istemci IP (30 gunluk onbellekle uyumlu),
     *   - JSON alani **`country_code`** (ip-api'nin `countryCode`'i DEGIL —
     *     bu yuzden ayristirma ayrica yazildi ve birim testiyle sabitlendi),
     *   - hata yolu: HTTP 404 + `{"success":false,"message":...}`.
     *
     * ALAN SOZLESMESI:
     *   `?fields=success,message,country_code` -> `{"success":true,"country_code":"US"}`
     *   (team member 144 olcum: 0.20-0.26 sn; bu gorevde tekrar olcum: 0.68 sn).
     *
     * YEDEK (ileride gerekirse): freeipapi.com
     *   `https://free.freeipapi.com/api/v1/json/{ip}` — alan `countryCode`
     *   ve NULL kontrolu ZORUNLU (gecersiz IP'de 200 OK + null doner).
     *   REDDEDILENLER: ipapi.co (ucretsizde HTTPS 'Paid Plan' ozelligi),
     *   ipinfo.io (legacy anahtarsiz uc nokta belgelenmemis).
     *
     * KALICI NOT — TLS CA DEPOSU (Linux dagitimlari):
     *   `file_get_contents` + `https://` sertifika dogrulamasini OpenSSL'in CA
     *   deposuna baglar. Bazı Linux dagitimlarinda php.ini'de `openssl.cafile`
     *   BOS birakilir ve paket CA dosyalari (openssl.crt / ca-certificates.crt)
     *   OpenSSL'in varsayilan dizininde bulunmaz. Boyle bir durumda dogrulama
     *   BASARISIZ olur, `file_get_contents` `false` doner ve kod yine de `XX`
     *   doner — yani HATA YOLU BOZULMAZ (fail-closed yonu korunur). Cozum:
     *   `openssl.cafile` / `openssl.capath` ayarlanmali ya da `SSL_CERT_FILE`
     *   ortam degiskeni verilmelidir.
     *   !!! DIKKAT: `verify_peer` KAPATILAMAZ (YASAK). Kapatilirsa G-08 "duz
     *   HTTP"den daha kotu bir durum olur: MITM'e acik sahte ulke kodu.
     *   `ssl` baglaminda `verify_peer` ve `verify_peer_name` ACIK yazilmistir.
     */
    private const GEOIP_UC_NOKTA = 'https://ipwho.is/{ip}?fields=success,message,country_code';

    /** ipwho.is sertifika dogrulamasi icin sunucu adi (SNI + peer dogrulamasi). */
    private const GEOIP_SUNUCU = 'ipwho.is';

    /** Ag zaman asimi (saniye). Onceki deger AYNEN korundu. */
    private const GEOIP_TIMEOUT_SN = 2;

    /** Kabul edilecek en fazla yanit govdesi (bayt) — asiri buyuk govde reddedilir. */
    private const GEOIP_MAX_GOVDE_BAYT = 4096;

    private static ?array $flags = null;

    /**
     * Get Country Code from IP Address.
     */
    public function getCountryCode(string $ip): string
    {
        // 0. Global Kill-Switch Check via Shield Repository 🛡️🛰️
        // [FW-110 / 97-1] Anayasa §8: DB okuyan sınıf Repository'dir; eski
        // `shieldDbSettings` PROVIDER'i kaldırıldı. Normalizasyon tek merkezde.
        try {
            $status = $this->repository('common.shieldSetting')
                ->getSetting(ShieldSettingsRepository::KEY_GEOIP_STATUS, true);
            if (!ShieldSettingsRepository::normalizeSwitch($status, true)) {
                return self::BILINMEYEN_ULKE;
            }
        } catch (\Throwable $e) {
            // Silently proceed
        }

        // [G-16] "Belirtilmemis" adres (`0.0.0.0`, `::`): ozel-IP dalindan
        // ONCE ele alinir. `TR` demek yanlis veri olurdu; `XX` dogru.
        if ($this->isUnspecifiedAddress($ip)) {
            return self::BILINMEYEN_ULKE;
        }

        // Localhost/Private IP check — MEVCUT DAVRANIS KORUNDU (ulke 'TR').
        // [G-16] Kapsam genisletildi: CGNAT/IPv6 ozel araliklar da burada
        // yakalanir -> dis servise (ipwho.is) GIDILMEZ.
        if ($this->isPrivateIp($ip)) {
            return 'TR';
        }

        // GECERLI BIR IP DEGILSE DIS ISTEK ATMA (yeni, FW-GEOIP-IPWHO-146).
        // Gerekce: bozuk/ele gecirilmis bir istemci IP'si saglayiciya
        // gonderilmemeli; hata yolu zaten 'XX' oldugu icin davranis degismez,
        // yalniz GECERSIZ bir dis cagri yapilmaz. Gecerli ozel IP yukarida
        // yakalandigi icin buraya yalniz gecersiz/genel dis adresler gelir.
        if (!$this->isValidIp($ip)) {
            return self::BILINMEYEN_ULKE;
        }

        $cacheKey = sprintf('security_geoip_%s', $ip);

        return $this->storage->cache()->remember($cacheKey, function () use ($ip) {
            try {
                // [G-08 KAPANDI] Sorgu `https://` uzerinden gider ve sertifika
                // DOGRULANIR (`verify_peer` ACIK — kapatmak YASAK).
                //
                // [G-08] `follow_location => 0`: uc nokta bir noktaya yonlendirirse
                // istemci sessizce duz HTTP'ye DUSMESIN (ipwho.is http'yi de
                // kabul ediyor; kod https sabit yazdigi icin dusmez, yine de
                // kilitli).
                $ctx = stream_context_create([
                    'http' => [
                        'timeout'         => self::GEOIP_TIMEOUT_SN,
                        'follow_location' => 0,
                        'ignore_errors'   => true,
                        'header'          => "Accept: application/json\r\n",
                    ],
                    'ssl' => [
                        'verify_peer'      => true,
                        'verify_peer_name' => true,
                        'allow_self_signed' => false,
                        'peer_name'        => self::GEOIP_SUNUCU,
                    ],
                ]);
                // [RBN Framework] Using a more robust API endpoint or local DB if available
                $ucNokta = self::urlFor(self::GEOIP_UC_NOKTA, $ip);
                $response = @file_get_contents($ucNokta, false, $ctx);

                if (!is_string($response) || $response === '') {
                    // AĞ/HTTP/TLS hatası: istek dusturulmaz, ulke atlanir.
                    // [A-27] Artık SESSİZ değil: kısaltılmış (throttle) bir kayıt
                    // yazılır. Anahtar IP DEĞERİ içermez (PII yok).
                    return $this->hataYolu('ag_http_tls');
                }
                if (strlen($response) > self::GEOIP_MAX_GOVDE_BAYT) {
                    return $this->hataYolu('govde_cok_buyuk');
                }

                return self::ulkeKoduAyikla($response);
            } catch (\Throwable $e) {
                // Hata yolu: istek dusturulmaz, sadece ulke bilgisi atlanir.
                // [A-27] Istisna YOKSARI: burada yeniden firlatilmiyor (fail-closed
                // yonu burada yanlis olurdu) ama kayit birakilir.
                return $this->hataYolu('istisna');
            }
        }, 86400 * 30); // Cache for 30 days
    }

    /**
     * [A-27] Hata yolu: GÜVENLİ VARSAYILAN + kayıt, istisna YOK.
     *
     * SÖZLEŞME:
     *   1. Dönen değer **dolu ve güvenli**dir (`BILINMEYEN_ULKE` = 'XX'); asla
     *      `null` / `''` / boş dizi dönmez. Çağıran taraf `if ($ulke === '')`
     *      gibi bir boşluk kontrolüyle "ülke bilinmiyor" ayrımını yapamaz.
     *   2. **İstisna fırlatılmaz.** GeoIP hatası isteği düşürmemelidir; burada
     *      fail-closed yönü YANLIŞ olurdu (bakım modu yorumu).
     *   3. SESSİZ DEĞİLDİR: `LogThrottle::once()` ile kısaltılmış bir `warning`
     *      kaydı yazılır. Anahtar yalnız hata KALEBİ adını taşır — IP veya
     *      başka bir değer (PII) kayda girmez.
     *
     * @param string $kalep Hata sınıfı etiketi (makine tarafından sabitlenir).
     */
    private function hataYolu(string $kalep): string
    {
        if (LogThrottle::once('geoip_' . $kalep, 900)) {
            try {
                $this->logs()->warning('GeoIP saglayicisi yanit vermedi; ulke bilgisi atlandi.', [
                    'kalep' => $kalep,
                    'varsayilan' => self::BILINMEYEN_ULKE,
                ], 'geoip');
            } catch (\Throwable $yazmaHatasi) {
                // Kayıt yazılamazsa da karar DEĞİŞMEZ: hata yolu yine 'XX'.
            }
        }

        return self::BILINMEYEN_ULKE;
    }

    /**
     * ipwho.is JSON govdesinden iki harfli ulke kodunu ayiklar.
     *
     * OZELLIK: **saf fonksiyon** — ag istegi YOK, dosya sistemi YOK, durum
     * YOK. Boylece birim testi sahte yanitlarla calistirilabilir
     * (bkz. fw_geoip_ipwho.php).
     *
     * SARTLAR (hepsi saglanmazsa BILINMEYEN_ULKE doner):
     *   1. govde ayristirilabilir JSON nesnesi olmali,
     *   2. `success` === true olmali (ipwho.is hata yolu: 404 +
     *      `{"success":false,"message":"404 not found"}`),
     *   3. `country_code` bos olmamali ve TAM OLARAK 2 harf olmali.
     *
     * @param string|null $govde Ham HTTP yanit govdesi (veya null = istek basarisiz)
     */
    public static function ulkeKoduAyikla(?string $govde): string
    {
        if ($govde === null || $govde === '') {
            return self::BILINMEYEN_ULKE;
        }
        $data = json_decode($govde, true);
        if (!is_array($data)) {
            return self::BILINMEYEN_ULKE; // ayristirma hatasi / HTML / kesik JSON
        }
        if (!array_key_exists('success', $data) || $data['success'] !== true) {
            return self::BILINMEYEN_ULKE; // saglayici hata yolu (404 vb.)
        }
        $kod = $data['country_code'] ?? null;
        if (!is_string($kod) || preg_match('/^[A-Za-z]{2}$/', $kod) !== 1) {
            return self::BILINMEYEN_ULKE; // null / bos / bozuk kod
        }
        return strtoupper($kod);
    }

    /**
     * Sabit uc nokta sablonundan gercek URL'i uretir ve IP'yi guvenle
     * kodlar (`rawurlencode`) — saglayiciya degistirilmis IP gonderilmez.
     */
    private static function urlFor(string $sablon, string $ip): string
    {
        return str_replace('{ip}', rawurlencode($ip), $sablon);
    }

    /**
     * Get Emoji Flag from Country Code.
     */
    public function getFlag(string $countryCode): string
    {
        $this->loadFlags();
        return self::$flags[$countryCode]['emoji'] ?? '🏳️';
    }

    /**
     * Get Full Country Name from Code.
     */
    public function getCountryName(string $countryCode): string
    {
        $this->loadFlags();
        return self::$flags[$countryCode]['name'] ?? ($countryCode === 'XX' ? 'Bilinmeyen' : $countryCode);
    }

    private function loadFlags(): void
    {
        if (self::$flags === null) {
            $path = Paths::frameworkRoot() . '/Resources/Data/Locations/countries.json';
            if (file_exists($path)) {
                $json = file_get_contents($path);
                self::$flags = json_decode($json, true) ?: [];
            } else {
                self::$flags = [];
            }
        }
    }

    /**
     * Gecerli bir IP adresi mi? (IPv4 veya IPv6, bicimsel dogrulama.)
     */
    private function isValidIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Check if IP is in Private/Local range.
     *
     * [G-16 · 2026-10-03 · team member] KAPSAM GENİŞLETİLDİ (IPv4 + IPv6).
     *
     * Ölçülen eksikler (birim testi `fw_gateguard_orta_dusuk.php` (G16-a),
     * fix ÖNCESİ 11 özel adresin 5'i kapsam dışıydı):
     *   - `100.64.0.1` / `100.127.255.254` → **CGNAT** (`100.64.0.0/10`,
     *     RFC 6598). Türkiye'de mobil operatör ziyaretçiye CGNAT adresi
     *     verir; özel sayılmadığı için ülke `XX` dönüyor ve — daha kötüsü —
     *     **operatör verisi üçüncü tarafa gönderiliyordu**. Artık gitmez.
     *   - `0.0.0.0` → "belirtilmemiş"; `TR` sayılmaz (`isUnspecifiedAddress()`
     *     ile `XX` döner) ama dış servise de gönderilmez.
     *   - `fd00::1` (ULA `fc00::/7`), `fe80::1` (link-local `fe80::/10`),
     *     `::1`, `::` → IPv6 özel aralıklar.
     *   - `::ffff:1.2.3.4` → IPv4 kurallarına indirgenir.
     *
     * TERS YÖN DE ÖLÇÜLDÜ ((G16-b) 6/6): aralıklar AŞIRI GENİŞLETİLMEDİ;
     * `100.63.255.255` ve `100.128.0.1` genel sayılmaya devam eder. `ip2long()`
     * 32-bit imzalı döner; IPv4 bloklarında `>=`/`<=` güvenlidir.
     */
    private function isPrivateIp(string $ip): bool
    {
        $ip = trim($ip);
        if ($ip === '') {
            return false;
        }

        // IPv4-eşlemeli IPv6 adresini IPv4 kurallarına indirger.
        if (str_starts_with(strtolower($ip), '::ffff:')) {
            $sonDort = substr($ip, 7);
            if (filter_var($sonDort, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
                $ip = $sonDort;
            }
        }

        // --- IPv6 (ikili bayt üzerinden, yazım biçiminden bağımsız) ---
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            $bayt = @inet_pton($ip);
            if (!is_string($bayt) || strlen($bayt) !== 16) {
                return false;
            }
            $b0 = ord($bayt[0]);
            $b1 = ord($bayt[1]);
            if (($b0 & 0xFE) === 0xFC) {
                return true;                    // ULA fc00::/7
            }
            if ($b0 === 0xFE && ($b1 & 0xC0) === 0x80) {
                return true;                    // link-local fe80::/10
            }
            if (strspn($bayt, "\x00") === 16) {
                return true;                    // ::  (belirtilmemis)
            }
            return substr($bayt, -16) === str_repeat("\x00", 15) . "\x01"; // ::1
        }

        // --- IPv4 ---
        $longIp = ip2long($ip);
        if ($longIp === -1 || $longIp === false) {
            return false;
        }

        $privateRanges = [
            '10.0.0.0|10.255.255.255',
            '172.16.0.0|172.31.255.255',
            '192.168.0.0|192.168.255.255',
            '169.254.0.0|169.254.255.255',
            '127.0.0.0|127.255.255.255',
            // [G-16] CGNAT / shared address space (RFC 6598).
            '100.64.0.0|100.127.255.255',
            // [G-16] "this network" / belirtilmemis (RFC 1122, RFC 6890).
            '0.0.0.0|0.255.255.255',
            // [G-16] multicast + reserved/broadcast: ziyaretci adresi olamaz,
            // dis servise gonderilmemeli (gizlilik + yanlis cografi).
            '224.0.0.0|239.255.255.255',
            '240.0.0.0|255.255.255.255',
        ];

        foreach ($privateRanges as $range) {
            [$start, $end] = explode('|', $range);
            if ($longIp >= ip2long($start) && $longIp <= ip2long($end)) {
                return true;
            }
        }

        return false;
    }

    /**
     * [G-16] "Belirtilmemiş" adres mi? (`0.0.0.0`, `::`)
     *
     * `isPrivateIp()` bunları ÖZEL sayar (dış istek yapılmaması için) ama
     * `TR` demek YANLIŞ VERİ olur; bu yüzden `getCountryCode()` özel-IP
     * dalından ÖNCE bu kontrolü yapar ve `XX` döner.
     */
    private function isUnspecifiedAddress(string $ip): bool
    {
        $ip = trim($ip);
        return $ip === '0.0.0.0'
            || $ip === '::'
            || $ip === '0:0:0:0:0:0:0:0'
            || $ip === '0000:0000:0000:0000:0000:0000:0000:0000';
    }
}
