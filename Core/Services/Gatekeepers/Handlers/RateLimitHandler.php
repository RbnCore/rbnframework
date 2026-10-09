<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Gatekeepers\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\Database\Repositories\Common\ShieldSettingsRepository;
use Rbn\Framework\Core\Support\Blueprints\Validations\RateLimitValidations;

/**
 * RateLimitHandler - The Frequency Control Actor 🛡️⚡
 * 
 * RBN Framework: Atomic actor for evaluating hits and penalties.
 */
class RateLimitHandler extends BaseComponent
{
    /** @var array Cached limits from Config and Settings */
    protected array $limits = [];

    /**
     * Initializes the handler with merged limits.
     */
    public function __construct()
    {
        parent::__construct();

        // 1. Load from Global Definitions (Universal Standards 🛰️)
        $this->limits = RateLimitValidations::LIMITS;

        // 2. Override with Dynamic Settings (Kill-Switch Check)
        // [A0-1 / G-02] `getSetting()` MODELDE degil, REPOSITORY'de yasar.
        // `model('shieldSetting')` bir model donerdi ve metot cagrisi
        // undefined-method hatasi verirdi. Tek-merkez: kayitli repository.
        try {
            $rateLimitOn = $this->repository('common.shieldSetting')
                ->getSetting(ShieldSettingsRepository::KEY_RATE_LIMIT, '1');
            // [FW-110 / 97-2] FAIL-CLOSED normalizasyon - TEK MERKEZ.
            // Onceki hali `if (!($v === '1' || $v === 1 || $v === true || $v === 'true'))`
            // idi: 'on', 'yes', '' (bos satir), '2', null ve yazim hatasi gibi
            // DEGERLER sıniri sessizce KAPATIYORDU (fail-OPEN). Normalizasyon
            // artik yalniz ACIKCA KAPALI sayilan degerleri kapatir; belirsizlik
            // guvenli taraf olan ACIK tarafa duser.
            if (!ShieldSettingsRepository::normalizeSwitch($rateLimitOn, true)) {
                $this->limits = [];
            }
        } catch (\Throwable $e) {
            // [A0-2] FAIL-CLOSED + SESSIZ OLMAYAN. Burada `return` YOK:
            // `$this->limits` `RateLimitValidations::LIMITS` olarak KALIR, yani
            // ayar tablosu okunamasa bile hiz siniri AKTIF olur. Fail-OPEN
            // olsaydi burada `$this->limits = []` olurdu ve 18 site limitsiz
            // kalirdi. Okuma hatasi SESSIZCE yutulmaz, loglanir (teşhis için).
            $this->logWarning('Hiz siniri ayari okunamadi; sabit sinirlar kullanilacak (fail-closed).', [
                'rule'  => 'rate_limit_settings_unreadable',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Koruma katmani uyarisini sessizce yutmaz (A0-2).
     *
     * Yazma basarisiz olursa karar DEGismez; sadece kayit duser.
     * Bos catch bilincli: karar zaten fail-closed verildi, log hatasi
     * guvenlik sonucunu degistiremez.
     *
     * @param array<string,mixed> $context
     */
    protected function logWarning(string $message, array $context): void
    {
        try {
            $this->logs()?->channel('ratelimit')->warning($message, $context);
        } catch (\Throwable) {
            // Log yazimi guvenlik kararini degistirmez.
        }
    }

    /**
     * Check if an identifier is blocked for a specific action.
     */
    public function isBlocked(string $identifier, string $action): bool
    {
        // [A0-3] Kanonik anahtar: cagiran `login` gonderiyor, tablo `frontend_login`
        // tutuyor. Eski hali `isset($this->limits[$action])` idi ve eslesmeyince
        // SESSIZCE `false` donuyordu (fail-OPEN) -> giris/kayit/sifre sifirlama
        // akislari limitsiz kaldi. Artik once kanoniklestirilir.
        $key = RateLimitValidations::canon($action);

        // Kill-switch kapaliysa `$this->limits` BOS olur. Bu durumda hicbir
        // eylem sinirli degildir; `isset()` kontrolu yanlis olarak "tanimsiz
        // eylem" sanilirdi. Ayri tutuyoruz: sonuc etkilemez.
        if ($this->limits === []) {
            return false;
        }

        if (!isset($this->limits[$key])) {
            // [B-2 / Y-5] FAIL-CLOSED, AMA FAIL-CRASH DEGIL.
            // Onceki hali burada `InvalidArgumentException` firlatiyordu ve
            // `FormGuardHandler`'in varsayilan eylemi `'default_form'` `LIMITS`te
            // olmadigi icin `action` VERMEYEN her `rateLimitEnabled` formu HTTP
            // 500'e dusuyordu (kullanici formu gonderemiyordu).
            //
            // Fail-closed MANTIGI KORUNUR: tanimsiz eylem "serbest" sayilmaz,
            // `RateLimitValidations::FALLBACK_LIMIT` (makul tavan) uygulanir.
            // Sessiz de degildir: teshis icin loglanir. `getLimit()`'in throw
            // eden sozlesmesi (A0-3 kabul testi) ayri yerde korunur.
            $this->logWarning('Hiz siniri tanimsiz eylem; guvenli varsayilan sinir uygulandi.', [
                'rule'       => 'rate_limit_unknown_action',
                'action'     => $action,
                'canonik'    => $key,
                'varsayilan' => RateLimitValidations::FALLBACK_LIMIT,
            ]);
            $limit = RateLimitValidations::FALLBACK_LIMIT;
        } else {
            $limit = $this->limits[$key];
        }

        // Rare cleanup (1% chance)
        if (mt_rand(1, 100) === 1) {
            $this->model('common.rateLimit')->cleanExpired($identifier, $key, $limit['window']);
        }

        $windowStart = time() - $limit['window'];

        // [A0-3] Sayim kanonik anahtarla yapilir; boylece `login` ile
        // `frontend_login` ayni kovayi paylasir (cift sayim OLMAZ).
        $attempts = $this->model('common.rateLimit')->getAttemptsCount($identifier, $key, $windowStart);

        return $attempts >= $limit['max'];
    }

    /**
     * Bir eylemin kanonik anahtarini dondurur (A0-3; UI/tehlike mesajlari icin).
     */
    public function canonicalAction(string $action): string
    {
        return RateLimitValidations::canon($action);
    }

    /**
     * Bir eylemin tanimli olup olmadigini dondurur (A0-3).
     *
     * UI/dashboard tarafinda "bu eylem sinirli mi?" sorusunu, hatayi
     * tetiklemeden yanitlamak icin. `isBlocked()` fail-closed oldugu icin
     * burada YONLU kontrol gerekiyor.
     */
    public function hasLimit(string $action): bool
    {
        return isset($this->limits[RateLimitValidations::canon($action)]);
    }

    /**
     * Record a new attempt.
     */
    public function recordAttempt(string $identifier, string $action, array $metadata = []): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 500);

        // Resolve Country Code via GeoIP Handler (Discovery based 🛰️)
        $countryCode = $this->service('ipGuard')->geoIPHandler->getCountryCode($ip);

        // [A0-3] Yazma da kanonik anahtara yapilir. Aksi halde `login` ile
        // sayilan kayit `frontend_login` kovasinda gorunmez ve sinir hicbir
        // zaman tetiklenmez (isBlocked sayimi ile yazim ayrilir).
        return $this->model('common.rateLimit')->recordAttempt(
            $identifier,
            RateLimitValidations::canon($action),
            $ip,
            $userAgent,
            $metadata,
            $countryCode
        );
    }

    /**
     * Get remaining time in minutes.
     */
    public function getRemainingTime(string $identifier, string $action): int
    {
        // [A0-3] Ayni kanonik anahtarla okunur (isBlocked ile ayni kova).
        $key = RateLimitValidations::canon($action);

        if ($this->limits === []) {
            return 0;
        }

        // [B-2 / Y-5] isBlocked() ile AYNI kovayi okur. Tanimsiz eylemde
        // `return 0` demek "kalan yok" (yani blok yok) yorumlanirdi; oysa
        // isBlocked() o eyleme fallback tavanini uyguluyor. Kullaniciya
        // "0 dakika sonra tekrar deneyin" yazmamak icin ayni fallback kullanilir.
        $limit = $this->limits[$key] ?? RateLimitValidations::FALLBACK_LIMIT;

        $windowStart = time() - $limit['window'];

        $lastAttempt = $this->model('common.rateLimit')->getLastAttempt($identifier, $key, $windowStart);

        if (!$lastAttempt) {
            return 0;
        }

        $lastAttemptTime = strtotime($lastAttempt['created_at']);
        $unblockTime = $lastAttemptTime + $limit['window'];
        $seconds = max(0, $unblockTime - time());

        return (int) ceil($seconds / 60);
    }

    /**
     * Hesap kilidi durumu (`remaining` > 0 ise kilitli; saniye).
     *
     * Sayaç girilen kimliğin özetiyle tutulur: var olmayan hesap da aynı
     * sayacı alır (kilit davranışı hesabın varlığını sızdırmaz). Okuma hatası
     * YUTULMAZ: çağıran (giriş akışı) istisnada girişi reddeder (fail-closed).
     *
     * @return array{failures:int,lock_seconds:int,remaining:int}
     */
    public function accountLockStatus(string $identity): array
    {
        if ($this->limits === []) {
            // `rate_limit` kill-switch kapalı: tüm sınırlar gibi bu da kapalı.
            return ['failures' => 0, 'lock_seconds' => 0, 'remaining' => 0];
        }

        return $this->accountLockState($identity);
    }

    /**
     * Başarısız girişi hesap sayacına yazar; yeni durumu döner.
     *
     * @return array{failures:int,lock_seconds:int,remaining:int}
     */
    public function recordAccountFailure(string $identity): array
    {
        if ($this->limits === []) {
            return ['failures' => 0, 'lock_seconds' => 0, 'remaining' => 0];
        }

        $this->recordAttempt(self::accountKey($identity), RateLimitValidations::ACCOUNT_LOGIN_ACTION);

        return $this->accountLockState($identity);
    }

    /**
     * Başarılı girişte hesap sayacını sıfırlar (yalnız bu hesabın satırları).
     */
    public function clearAccountFailures(string $identity): void
    {
        $this->model('common.rateLimit')
            ->where('identifier', self::accountKey($identity))
            ->where('action', RateLimitValidations::ACCOUNT_LOGIN_ACTION)
            ->delete();
    }

    /**
     * Kilit: eşiği dolduran (N.) denemenin zamanı + kademe süresi.
     *
     * @return array{failures:int,lock_seconds:int,remaining:int}
     */
    private function accountLockState(string $identity): array
    {
        $key = self::accountKey($identity);
        $action = RateLimitValidations::ACCOUNT_LOGIN_ACTION;
        $windowStart = time() - RateLimitValidations::ACCOUNT_LOCK_WINDOW;

        $failures = $this->model('common.rateLimit')->getAttemptsCount($key, $action, $windowStart);
        $step = RateLimitValidations::accountLockStep($failures);
        if ($step === null) {
            return ['failures' => $failures, 'lock_seconds' => 0, 'remaining' => 0];
        }

        // Pencere içindeki denemeler eskiden yeniye; eşiği dolduran satır.
        $row = $this->model('common.rateLimit')
            ->where('identifier', '=', $key)
            ->where('action', '=', $action)
            ->where('created_at', '>=', now('Y-m-d H:i:s', $windowStart))
            ->orderBy('created_at', 'ASC')
            ->orderBy('id', 'ASC')
            ->offset($step['threshold'] - 1)
            ->first();
        $row = is_object($row) && method_exists($row, 'toArray') ? $row->toArray() : (array) $row;

        // Satır okunamazsa en sıkı yorum: kilit şimdi başlamış sayılır (fail-closed).
        $reachedAt = isset($row['created_at']) ? strtotime((string) $row['created_at']) : false;
        $remaining = $reachedAt === false
            ? $step['seconds']
            : max(0, $reachedAt + $step['seconds'] - time());

        return ['failures' => $failures, 'lock_seconds' => $step['seconds'], 'remaining' => $remaining];
    }

    /**
     * Sayaç anahtarı: kimliğin kendisi tabloya yazılmaz, özeti yazılır.
     */
    private static function accountKey(string $identity): string
    {
        return 'acct:' . hash('sha256', mb_strtolower(trim($identity)));
    }

    /**
     * Get all limits (for debugging or UI).
     */
    public function getLimits(): array
    {
        return $this->limits;
    }
}
