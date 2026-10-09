<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Blueprints\Validations;
/**
 * RateLimitValidations - The Frequency Anayasa 🛡️⚡⚖️
 * 
 * RBN Framework: Master repository for universal rate limiting thresholds.
 * Centralizes security numbers to ensure consistent protection across all modules.
 */
class RateLimitValidations
{

    /**
     * Universal Rate Limits (Global Standards 🛰️)
     * Window is in seconds.
     */
    public const LIMITS = [
        // Authentication & Identity
        'frontend_login' => ['max' => 5, 'window' => 900],       // 15 mins
        'admin_login' => ['max' => 3, 'window' => 1800],      // 30 mins (Strict)
        'user_register' => ['max' => 5, 'window' => 3600],      // 1 hour
        'password_reset' => ['max' => 3, 'window' => 3600],      // 1 hour

        // Data Interaction
        'form_submission' => ['max' => 10, 'window' => 1200],     // 20 mins
        'contact_form' => ['max' => 3, 'window' => 3600],      // 1 hour
        'add_comment' => ['max' => 10, 'window' => 300],       // 5 mins
        'search_query' => ['max' => 30, 'window' => 600],       // 10 mins

        // API & Automation
        'api_request' => ['max' => 100, 'window' => 3600],     // 1 hour
        'ajax_request' => ['max' => 50, 'window' => 600],       // 10 mins

        // File & Resource Operations
        'file_upload' => ['max' => 20, 'window' => 3600],      // 1 hour
        'export_data' => ['max' => 5, 'window' => 3600],       // 1 hour
        'import_data' => ['max' => 3, 'window' => 3600],       // 1 hour
        'backup_create' => ['max' => 2, 'window' => 3600],       // 1 hour

        // Critical System Ops
        'settings_update' => ['max' => 10, 'window' => 600],       // 10 mins
        'cache_clear' => ['max' => 5, 'window' => 3600],       // 1 hour
        'bulk_update' => ['max' => 3, 'window' => 1800],       // 30 mins
        'email_send' => ['max' => 10, 'window' => 3600],      // 1 hour

        // Security Incident (Zero Tolerance 🛡️)
        'suspicious_activity' => ['max' => 1, 'window' => 3600],       // 1 per hour (Instant flag)

        /* --- [A0-3] Kanonik anahtarlar: `ACTION_ALIASES` bunlara işaret eder --- */

        // Genel amaçlı kova. `ComponentTypes` sonek eşlemesi ('action'=>'actions')
        // ve benzeri genel eylem adları buraya kanonikleşir. Amaç: eşleşmeyen
        // bir eylem **limitsiz** kalmasın; makul bir tavan uygulansın.
        // `getLimit()` yine de tanımsız adlarda throw eder — bu kova
        // yalnızca ALIAS tablosunun işaret ettiği kanonik addır.
        'action' => ['max' => 60, 'window' => 3600],      // 1 saat

        // SQL konsolu: tek kullanımlık, çok yüksek riskli eylem. Sınırı yoksa
        // kaba kuvvet/veri sızdırma aracı olurdu. Sıkı tutulur.
        'sql_console_execute' => ['max' => 10, 'window' => 3600], // 1 saat
    ];

    /* ========================================================================
       [ FW-BANU-B2-B3-Y4 / B-2 · Y-5 ]  BİLİNMEYEN EYLEM = 500 DEĞİL,
       LİMİTSİZ DE DEĞİL
       ========================================================================
       SORUN: `RateLimitHandler::isBlocked()` bilinmeyen eylemde **istisna**
       fırlatıyordu ve `FormGuardHandler`'ın varsayılan eylemi `'default_form'`
       `LIMITS` içinde YER ALMIYORDU. Sonuç: `rateLimitEnabled => true` verip
       `action` unutan bir form → `InvalidArgumentException` → HTTP 500,
       kullanıcı formu gönderemiyor. Yani fail-closed, **fail-crash** olmuştu.

       ÇÖZÜM (fail-closed MANTIĞI BOZULMADAN): bilinmeyen eylem artık
       - 500 VERMEZ,
       - limitsiz de KALMAZ: `FALLBACK_LIMIT` (makul tavan) uygulanır,
       - ve `RateLimitHandler` bunu LOG'A yazar (sessiz değil, teşhis var).
       `canon()` (tek merkez kanonikleştirme) ve `getLimit()`'in throw eden
       fail-closed sözleşmesi **aynen korunur**: `getLimit()` hâlâ istisna
       atar (kabul testi A0-3 bu sözleşmeyi ölçer); yalnız ÇALIŞMA ZAMANI
       yolu (`isBlocked`) istisna yerine güvenli tavan uygular.
       ======================================================================== */

    /**
     * Hesap (giriş kimliği) bazlı başarısız giriş sayacının eylem adı.
     * IP kovasından (`frontend_login`) ayrıdır: dağıtık (çok IP'li) parola
     * denemesi de aynı hesapta birikir.
     */
    public const ACCOUNT_LOGIN_ACTION = 'account_login';

    /** Hesap sayacının sayım penceresi (saniye): son 1 saat. */
    public const ACCOUNT_LOCK_WINDOW = 3600;

    /**
     * Artan hesap kilidi (TEK YER): eşik => kilit süresi (saniye). Büyükten küçüğe.
     *   5. başarısız deneme  -> 15 dakika
     *   10. başarısız deneme -> 1 saat
     * Kilit, eşiği DOLDURAN denemenin zamanından başlar. Kilit bitince bir
     * sonraki eşiğe kadar deneme serbesttir (5 -> 15 dk, sonra 5 deneme
     * daha -> 1 sa); her ek deneme kilidi yeniden başlatmaz.
     *
     * @var array<int,int>
     */
    public const ACCOUNT_LOCK_STEPS = [
        10 => 3600,
        5  => 900,
    ];

    /**
     * Pencere içindeki başarısız deneme sayısına göre geçerli kademe.
     *
     * @return array{threshold:int,seconds:int}|null kademe yoksa null
     */
    public static function accountLockStep(int $failures): ?array
    {
        foreach (self::ACCOUNT_LOCK_STEPS as $threshold => $seconds) {
            if ($failures >= $threshold) {
                return ['threshold' => $threshold, 'seconds' => $seconds];
            }
        }

        return null;
    }

    /**
     * Tanımsız eylem için güvenli varsayılan tavan (fail-closed, fail-crash DEĞİL).
     *
     * Değerler `form_submission` ile aynıdır: 20 dakikada 10 deneme. Sıkı bir
     * giriş sınırından gevşek, ama "limitsiz"den kat kat dar. Amaç: yeni bir
     * eylem adı unutulduğunda kullanıcı 500 yerine sınıra takılır, saldırgan
     * ise sınırsız deneme yapamaz.
     *
     * @var array{max:int,window:int}
     */
    public const FALLBACK_LIMIT = ['max' => 10, 'window' => 1200];

    /* ========================================================================
       [ A0-3 / F-22 ]  EYLEM ADI TEK KAYNAK + FAIL-CLOSED
       ========================================================================
       SORUN: `LIMITS` anahtarları ile çağıranların gönderdiği `action`
       değerleri UYUŞMUYORDU. Çağıranlar `login` / `register` / `recovery`
       gönderiyor, tablo `frontend_login` / `user_register` / `password_reset`
       tanımlıyordu. `RateLimitHandler::isBlocked()` `isset()` kontrolü
       başarısız olunca **sessizce `false` dönüyor** (fail-OPEN) → giriş,
       kayıt ve şifre sıfırlama akışları limitsiz kalmıştı.

       ÇÖZÜM (iki katmanlı, en dar düzeltme):
         1) `canon()` — eylem adını TEK merkezden kanonik anahtara çevirir
            (alias tablosu). Böylece geçmişteki `login` çağrısı da doğru
            sınıra düşer; çağıran kod değiştirilmeden de hız sınırı çalışır.
         2) `getLimit()` bilinmeyen eylemde **throw** eder (fail-closed).
            `?? ['max'=>10]` sessiz varsayılanı fail-OPEN'dı.

       Yeni eylem adı eklerken: `LIMITS`'e gerçek bir karşılık verin.
       `sql_console_execute` gibi TEK-KULLANIMLIK, yüksek riskli eylemler
       `EKSİK EYLEM` listesinde tanımlıdır — `canon()` onları doğrudan
       kendi adlarıyla geçirir.
       ======================================================================== */

    /**
     * Alias -> kanonik anahtar eşlemesi (A0-3).
     *
     * Anahtar sol taraf: çağıranların gönderdiği (veya geçmişte gönderdiği)
     * eylem adı. Sağ taraf: `LIMITS` içindeki gerçek anahtar.
     *
     * @var array<string,string>
     */
    public const ACTION_ALIASES = [
        'login'     => 'frontend_login', // AuthController::loginSubmit
        'register'  => 'user_register',  // AuthActionController::registerSubmit
        // `recovery` KALICI ALIAS'tir (geriye donuk uyum): eski cagiranlar bu
        // adi gonderiyordu. Bugun cagiranlar `password_reset` gonderiyor ve o
        // ad zaten `LIMITS` anahtari oldugu icin `canon()` kendiliginden ayni
        // kovaya duser. Iki yazim da `password_reset` kovasini paylasir (cift
        // sayim OLMAZ) — A0-6 kabul testi bu eslesmeyi olcer.
        'recovery'  => 'password_reset', // eski sifre sifirlama istek adi
        'actions'   => 'action',         // ComponentTypes: 'action' => 'actions' (sonek eslesmesi)

        // [B-2 / Y-5] `FormGuardHandler` `action` VERMEYEN her `rateLimitEnabled`
        // formu icin bu adi kullanir. Once `LIMITS`te olmadigi icin yol 500
        // veriyordu. Artik acikca `form_submission` kovasina kanoniklenir.
        'default_form' => 'form_submission',
    ];

    /**
     * Tek kullanımlık / yüksek riskli eylemler.
     *
     * Bunlar `LIMITS` içinde KENDİ ADLARIYLA tanımlıdır; alias gerektirmez.
     * Buradaki varlıkları, `canon()` bunlara dokunmadan geçtiğini ve
     * kazara silinmediklerini garanti eder.
     *
     * @var string[]
     */
    public const DIRECT_ACTIONS = [
        'sql_console_execute', // SqlConsoleController — SQL konsolu
    ];

    /**
     * Eylem adını kanonik `LIMITS` anahtarına çevir (TEK MERKEZ).
     *
     * @param string $action Çağıranın gönderdiği ham eylem adı.
     * @return string Kanonik anahtar; karşılığı yoksa ham ad aynen döner.
     */
    public static function canon(string $action): string
    {
        $a = strtolower(trim($action));
        return self::ACTION_ALIASES[$a] ?? $a;
    }

    /**
     * Get specific limit for an action (fail-closed).
     *
     * [A0-3 / F-22] Önceki hali `self::LIMITS[$action] ?? ['max'=>10,'window'=>600]`
     * idi: bilinmeyen eylem sessizce gevşek bir sınıra düşüyordu (fail-OPEN).
     * Artık bilinmeyen eylem **istisna** üretir; çağıran ya doğru adı
     * göndermeli ya da bu hatayı yüzeye taşımalıdır.
     *
     * @throws \InvalidArgumentException Eylem `LIMITS` içinde tanımsızsa.
     */
    public static function getLimit(string $action): array
    {
        $key = self::canon($action);
        if (!isset(self::LIMITS[$key])) {
            throw new \InvalidArgumentException(
                'Hiz siniri tanimsiz eylem: "' . $action . '" (canonik: "' . $key . '"). '
                . 'RateLimitValidations::LIMITS icine eklenmeli.'
            );
        }
        return self::LIMITS[$key];
    }

    /**
     * Check if an action has a defined limit (alias duyarli).
     */
    public static function hasLimit(string $action): bool
    {
        return isset(self::LIMITS[self::canon($action)]);
    }

    /**
     * [B-2 / Y-5] ÇALIŞMA ZAMANI sınır çözümleyici: tanımlı değilse
     * `FALLBACK_LIMIT` (güvenli tavan) döner — **istisna atmaz**.
     *
     * `getLimit()` fail-closed sözleşmesini korur (bilinmeyen eylem orada hata
     * üretir). Buradaki kardeş metot, HTTP isteği içinde çağrılan yolun 500
     * vermesini engeller: kullanıcı hata ekranı görmek yerine sınıra takılır.
     * Çağıran (RateLimitHandler) tanımsızlığı log'a yazmakla yükümlüdür.
     *
     * @return array{max:int,window:int}
     */
    public static function limitOrFallback(string $action): array
    {
        $key = self::canon($action);
        return self::LIMITS[$key] ?? self::FALLBACK_LIMIT;
    }
}
