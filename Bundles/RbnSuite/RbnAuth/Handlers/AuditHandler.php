<?php

namespace Rbn\Framework\Bundles\RbnSuite\RbnAuth\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * AuditHandler - Specialized motor for Security Auditing 🛡️🖊️⚓
 * RBN 3.5 Masterpiece Standard.
 * 
 * Orchestrates multi-channel logging (File, DB, Shield).
 */
class AuditHandler extends BaseComponent
{
    /** Maskeleme ciktisinda gosterilecek en fazla karakter (A-21). */
    private const MASK_MAX_LENGTH = 64;

    /**
     * Kimlik bilgisini (e-posta / kullanıcı adı) loglanabilir hâle getirir 🛡️
     *
     * [A-21] ÖNCEKİ HALİ: `logFailure()` kullanıcının GİRDİĞİ kimliği dosya
     * loguna düz metin yazıyordu (`"Giriş Başarisiz: $identity - Sebep: …"`).
     * İki sonuç: (a) **log enjeksiyonu** — kimlik alanına satır sonu
     * konunca saldırgan kendi "Giriş Başarılı" satırlarını üretebiliyor,
     * (b) **KVKK** — kullanıcı olmasa bile rastgele girdiği veri kalıcı
     * log dosyasına yazılıyordu.
     *
     * KURAL: (1) kontrol karakterleri (satır sonu dâhil) atılır,
     * (2) sonuç MASKELİ alan adı/yerel parça ile kısaltılır,
     * (3) toplam uzunluk 64 karakterle sınırlıdır (log şişmesi).
     *
     * @return string `a***@***.com` / `ab***` / `***`
     */
    public static function maskIdentity(string $identity): string
    {
        // 1) Kontrol karakterleri ve fazla bosluk -> log satiri enjeksiyonu kapat
        $clean = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $identity) ?? '';
        $clean = trim(preg_replace('/\s+/u', ' ', $clean) ?? '');
        if ($clean === '') {
            return '***';
        }

        // 2) Uzunluk siniri (log flooding / tablo sismesi)
        if (mb_strlen($clean) > self::MASK_MAX_LENGTH) {
            $clean = mb_substr($clean, 0, self::MASK_MAX_LENGTH);
        }

        // 3) E-posta: yerel parca ilk 1 karakter + alan adi yalniz uzuntisi
        if (str_contains($clean, '@')) {
            [$local, $domain] = explode('@', $clean, 2);
            $tld = '';
            $dot = strrpos($domain, '.');
            if ($dot !== false && $dot < strlen($domain) - 1) {
                $tld = '.' . mb_substr($domain, $dot + 1);
            }
            return (mb_substr($local, 0, 1) ?: '*') . '***@***' . $tld;
        }

        // Kullanici adi: ilk 2 karakter + maske
        return mb_substr($clean, 0, 2) . '***';
    }

    /**
     * Serbest metin alanlarını (sebep vb.) tek satırlı sınırlı hâle getirir.
     *
     * [A-21] Log satırı enjeksiyonuna karşı: kontrol karakterleri atılır,
     * uzunluk kesilir. BOŞ İÇERİKTE **null DEĞİL** tire döner ki log
     * satırındaki alan kaybolmasın.
     */
    public static function sanitizeText(string $value, int $maxLength = 200): string
    {
        $clean = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value) ?? '';
        $clean = trim(preg_replace('/\s+/u', ' ', $clean) ?? '');
        if ($clean === '') {
            return '-';
        }
        return mb_strlen($clean) > $maxLength ? mb_substr($clean, 0, $maxLength) : $clean;
    }

    /**
     * Logs a successful login event ✅
     */
    public function logSuccess(int $userId, string $identity, string $accountType = 'user'): void
    {
        $ip = $this->request->ip();
        $countryCode = $this->handler('geoIP')->getCountryCode($ip);

        // 🎼 Step 1: File Logging 📁
        // [A-21] Kimlik MASKELİ yazılır (log yüzeyinde düz metin yok).
        $label = strtoupper($accountType);
        $this->storage->logs()->channel('auth')->info(
            "Giriş Başarılı [$label]: ID $userId (" . self::maskIdentity($identity) . ") [IP: $ip] [KOD: $countryCode]"
        );

        // 🎼 Step 2: DB Logging via Repository 📊
        $this->repository('project.userActivity')->logSuccessfulActivity('login', $userId, $identity, $accountType, $countryCode);

        // 🎼 Step 3: Redemption Logic (YALNIZ giriş-hata sayacı) 🕊️🧹⚓
        $this->clearLoginFailureCounters($ip);
    }

    /**
     * [A-20 · 2026-10-04 · zeki-6eb7f5] BAŞARILI GİRİŞ SONRASI TEMİZLİK —
     * YALNIZ GİRİŞ HATA SAYACI. 🛡️
     *
     * ÖNCEKİ HALİ (`logSuccess` içinde inline, iki işlem):
     *   1. `$this->repository('project.userSecurity')->unblockIp($ip)` →
     *      `UserSecurityRepository::unblockIp()` `cm_sys_ip_blocks` tablosunda
     *      o IP'nin **NEDENİ NE OLURSA OLSUN TÜM** satırlarını siler.
     *      Ölçülen sonuç: tek bir meşru kullanıcının başarılı girişi, aynı
     *      NAT/CGNAT/CGNAT IP'sindeki **saldırganın** WAF/global/form banını
     *      da düşürüyordu = yasağın kendisi saldırganın elinde bir düğmeye
     *      dönüşüyordu (meşru kullanıcı o IP'den girince "kurbanın banı" kalıyor
     *      ya da meşru kullanıcı saldırganın banını kaldırıyordu — ikisi de
     *      yanlış). Ban kayıtlarına **DOKUNULMAZ**.
     *
     *   2. `common.rateLimit` temizliği `where('action','login')` ile
     *      yapılıyordu; oysa yazma yolu (`RateLimitHandler::recordAttempt()`)
     *      satırları `RateLimitValidations::canon()` ile **kanonik** adla
     *      yazar (`login` → `frontend_login`). Ölçüldü: filtre hiçbir satıra
     *      uymuyor → "temizleme" sessiz bir NO-OP'tu, yani kısa ömürlü giriş
     *      kovası biriken hatalı girişlerle dolu kalıyordu.
     *
     * KURALLAR:
     *   - Bu metot yalnız **giriş hata sayacını** sıfırlar.
     *   - IP ban kayıtları (`cm_sys_ip_blocks`) ve master `ip_blocks`
     *     KESİNLİKLE silinmez → saldırganın yasağı kendiliğinden kalkmaz.
     *   - Temizlik `ip_address` **ve** kanalonik eylem adı ile KAPSAMlidir:
     *     başka formların (`password_reset`, `user_register`, ...) sayaçları
     *     korunur.
     *   - Hata (DB erişimi) giriş akışını bozmaz: `catch` ile yutulur,
     *     giriş yine BAŞARILIDIR (temizlik yardımcı işlemdir).
     *
     * @param string $ip `REMOTE_ADDR` (güvenilir sunucu tarafı)
     */
    public function clearLoginFailureCounters(string $ip): bool
    {
        if ($ip === '') {
            return false;
        }

        try {
            // Kanonik eylem adi: tek kaynak `RateLimitValidations` (A0-3).
            // Ham 'login' yazmak sessiz bir NO-OP uretiyordu.
            $eylem = \Rbn\Framework\Core\Support\Blueprints\Validations\RateLimitValidations::canon('login');

            $silinen = $this->model('common.rateLimit')
                ->where('ip_address', $ip)
                ->where('action', $eylem)
                ->delete();

            return $silinen > 0;
        } catch (\Throwable $e) {
            // Temizlik BASARISIZ olursa giriş yine gecerli kalir.
            error_log('RBN Guvenlik: giris sonrasi hata sayaci temizlenemedi [' . get_class($e) . ']');
            return false;
        }
    }

    /**
     * Logs a failed login attempt 🛑
     *
     * [A-21] Dosya logu artık MASKELİ kimlik + tek satırlı sınırlı sebep yazar.
     * ÖNCEKİ HALİ: `"Giris Basarisiz: $identity - Sebep: $reason"` — kullanıcı
     * GİRDİĞİ ham metin, satır sonu süzgecinden geçmeden log dosyasına
     * yazılıyordu (log enjeksiyonu + KVKK).
     *
     * NOT: DB'ye yazılan `logFailedActivity()` kaydı **düzeltilmedi** — panelde
     * "başarısız giriş denemeleri" listesi bu kayda dayanıyor; maske uygulanırsa
     * inceleme/savunma yeteneği kaybolur. Kapatılan yüzey **log dosyasıdır**.
     */
    public function logFailure(string $identity, string $reason, string $accountType = 'user'): void
    {
        $ip = $this->request->ip();
        $countryCode = $this->handler('geoIP')->getCountryCode($ip);

        // 🎼 Step 1: File Logging 📁
        $this->storage->logs()->channel('auth')->warning(
            'Giriş Başarısız: ' . self::maskIdentity($identity)
            . ' - Sebep: ' . self::sanitizeText($reason)
            . " [IP: $ip] [KOD: $countryCode]"
        );

        // 🎼 Step 2: DB Logging via Repository 📊
        $this->repository('project.userActivity')->logFailedActivity('login', $identity, $reason, $accountType, $countryCode);
    }

    /**
     * Logs a security-related block 🚫🛡️⚓
     */
    public function logSecurityBlock(string $ip, string $reason): void
    {
        // 🎼 Step 1: File Logging 📁
        $this->storage->logs()->channel('auth')->error("GÜVENLİK ENGELİ: IP [$ip] - Sebep: $reason");

        // 🎼 Step 2: DB Logging via Repository 📊
        $userActRepo = $this->repository('project.userActivity');
        if ($userActRepo && method_exists($userActRepo, 'logFailedActivity')) {
            $userActRepo->logFailedActivity('security_block', $ip, $reason, 'user');
        }
    }
}
