<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Repositories\Project;

use Rbn\Framework\Core\Base\Data\BaseRepository;

/**
 * UserTokenRepository - Tek Kullanımlık Token Kasası Repository 📦🔐
 *
 * [A0-6] Anayasa §8: "Veritabanı bağlantısı yapan = Repository." Bu sınıf
 * `z_user_tokens` tablosunun **tek** yazma/okuma kapısıdır; `CredentialHandler`
 * ve `LifecycleHandler` doğrudan model çağırmaz.
 *
 * Tasarım sözleşmesi (kabul testlerinin ölçtüğü kurallar):
 *  1. Token **kriptografik rastgele**: `random_bytes(32)` -> 64 hex karakter.
 *  2. DB'de **yalnız hash**: `hash('sha256', $raw)`. Ham token hiçbir zaman yazılmaz.
 *  3. **Tek kullanımlık**: `consume()` satırı `used_at` ile mühürler; ikinci
 *     kullanım `used_at IS NOT NULL` olduğu için bulunamaz.
 *  4. **Kısa ömür**: `expires_at`; `consume()` süresi dolmuş satırı reddeder.
 *  5. **Kullanıcıya bağlı**: `user_id` satırda taşınır; `user_id` *token'dan* türetilir.
 *  6. **Zamanlama-güvenli**: `hash_equals()` ile karşılaştırma.
 *  7. **Yeniden üretim**: yeni token üretilince o kullanıcının o amaçtaki bekleyen
 *     token'ları iptal edilir; başarılı kullanımdan sonra da tüm bekleyenler düşer.
 */
class UserTokenRepository extends BaseRepository
{
    /** @var string Target primary model alias */
    protected $targetModel = 'project.userToken';

    /** E-posta doğrulama token'ı: 24 saat. */
    public const TTL_EMAIL_VERIFICATION_MINUTES = 1440;

    /** Parola sıfırlama token'ı: 60 dakika. */
    public const TTL_PASSWORD_RECOVERY_MINUTES = 60;

    /** Geçerli amaçlar — bilinmeyen amaç sessizce yanlış kolona düşmez. */
    public const PURPOSES = ['email_verification', 'password_recovery'];

    /* ==========================================================================
       [ WRITE WORKERS ] 🖊️
       ========================================================================== */

    /**
     * Yeni bir tek kullanımlık token üretir ve **ham token'ı** döndürür
     * (ham token yalnız e-postaya konur, DB'ye yazılmaz).
     *
     * @throws \InvalidArgumentException Amaç tanımsızsa (YA-5: sessiz yanlış kolon yazımı olmasın)
     */
    public function issue(int $userId, string $purpose, int $ttlMinutes, ?string $ip = null): string
    {
        if (!in_array($purpose, self::PURPOSES, true)) {
            throw new \InvalidArgumentException('Bilinmeyen token amacı: ' . $purpose);
        }
        if ($userId <= 0) {
            throw new \InvalidArgumentException('Token üretimi için geçerli bir kullanıcı gerekir.');
        }

        // 🧬 1) Kriptografik rastgele token (CSPRNG, 32 bayt)
        $raw = bin2hex(random_bytes(32));

        // 🗑️ 2) Aynı kullanıcı + aynı amaçtaki bekleyen token'ları iptal et
        $this->revokePending($userId, $purpose);

        // 🧾 3) Yalnız hash'i yaz
        $model = $this->model('project.userToken');
        if (!$model) {
            throw new \RuntimeException('Token kasası kullanılamıyor (model kayıtlı değil).');
        }

        $ok = $model->create([
            'user_id'    => $userId,
            'purpose'    => $purpose,
            'token_hash' => hash('sha256', $raw),
            'expires_at' => date('Y-m-d H:i:s', time() + ($ttlMinutes * 60)),
            'used_at'    => null,
            'ip'         => $ip,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if (!$ok) {
            throw new \RuntimeException('Token kaydedilemedi.');
        }

        return $raw;
    }

    /* ==========================================================================
       [ READ WORKERS ] 🔍
       ========================================================================== */

    /**
     * Token'ı tek seferlik kullanır ve doğrulayan kullanıcıyı döndürür.
     *
     * @return array{user_id:int,row:array}|array{error:string}
     *         error: not_found | used | expired | wrong_purpose
     */
    public function consume(string $rawToken, string $purpose): array
    {
        $model = $this->model('project.userToken');
        if (!$model || $rawToken === '') {
            return ['error' => 'not_found'];
        }

        $hash = hash('sha256', $rawToken);
        $row = $this->rowToArray($model->where('token_hash', $hash)->first());
        if ($row === null) {
            return ['error' => 'not_found'];
        }

        // 🛡️ Zamanlama-güvenli karşılaştırma (indeksli eşitlik + sabit zamanlı doğrulama)
        if (!hash_equals((string) ($row['token_hash'] ?? ''), $hash)) {
            return ['error' => 'not_found'];
        }

        // 🎯 Amaç eşleşmesi: doğrulama token'ı parola sıfırlama yerine geçemez
        if (($row['purpose'] ?? '') !== $purpose) {
            return ['error' => 'wrong_purpose'];
        }

        if (!empty($row['used_at'])) {
            return ['error' => 'used'];
        }

        if (strtotime((string) ($row['expires_at'] ?? '')) <= time()) {
            return ['error' => 'expired'];
        }

        // 🔒 Tek kullanımlık: satırı mühürle — TEK ve ATOMİK adım.
        // [B-3] Önceki hali SELECT ... [burada boşluk] ... koşulsuz UPDATE idi:
        // aynı token iki eşzamanlı istekte (iki sekme, çift tıklama) İKİSİ DE
        // `used_at = NULL` görür, ikisi de geçer, ikisi de yazar → parola sıfırlama
        // token'ı İKİ KEZ kullanılabiliyordu (A0-6 "tek kullanımlık" ihlali).
        // Artık mühürleme TEK `UPDATE` ve KOŞULLUDUR (`used_at IS NULL` +
        // süre kontrolü); hangi istekin kazandığını ETKİLENEN SATIR SAYISI
        // söyler. 1 satır → bu istek kazandı. 0 satır → başka bir istek
        // tüketti (ya da süre doldu) → bu istek BAŞARISIZ.
        if (!$this->sealToken((int) $row['id'])) {
            return ['error' => $this->sealRejectionReason((int) $row['id'])];
        }

        // ♻️ Başarılı kullanımdan sonra o kullanıcının bu amaçtaki TÜM bekleyen
        //    token'ları düşer (aynı anda üretilmiş eski bağlantılar geçersiz).
        $this->revokePending((int) $row['user_id'], $purpose);

        return ['user_id' => (int) $row['user_id'], 'row' => $row];
    }

    /**
     * [B-3] Tek kullanımlık mührü — TEK, ATOMİK, KOŞULLU `UPDATE`.
     *
     * MySQL satır kilidi sayesinde `used_at IS NULL` koşulu kendisi
     * compare-and-set'tir: aynı satıra eşzamanlı gelen iki istekten yalnız
     * BİRİ 1 satır etkiler, diğeri 0 alır. Uygulama tarafında ayrı bir
     * kilitleme/eşzamanlılık mekanizmasına GEREK YOKTUR (ve yarış koşulu
     * kendiliğinden doğmaz). `expires_at` koşulu da aynı cümlede yeniden
     * denetlenir: SELECT ile UPDATE arasında geçen sürede süresi dolan token
     * kabul edilmez.
     *
     * @return bool true = bu istek mührü attı (kazandı), false = başka istek
     *         tüketti veya token artık gecerli degil.
     */
    protected function sealToken(int $id): bool
    {
        $model = $this->model('project.userToken');
        if (!$model || $id <= 0) {
            return false;
        }

        return $model->where('id', $id)
            ->whereNull('used_at')
            ->where('expires_at', '>', date('Y-m-d H:i:s'))
            ->updateAffected(['used_at' => date('Y-m-d H:i:s')]) === 1;
    }

    /**
     * [B-3] Mühürleme reddedildiyse nedenini döner (`consume()` sözleşmesi).
     *
     * `consume()` çağıranları `used` / `expired` ayrımını kullanıyor
     * ("bu bağlantı kullanıldı" mı "süresi doldu" mu). Reddedilen satır
     * yeniden okunur; okunamazsa en güvenli yanıt `used` (tekrar deneme
     * kazandırmaz).
     */
    protected function sealRejectionReason(int $id): string
    {
        $model = $this->model('project.userToken');
        if (!$model) {
            return 'used';
        }
        $row = $this->rowToArray($model->where('id', $id)->first());
        if ($row !== null && empty($row['used_at'])
            && strtotime((string) ($row['expires_at'] ?? '')) <= time()) {
            return 'expired';
        }

        return 'used';
    }

    /**
     * first() sonucu (model NESNESİ ya da dizi) tek biçime çevirir; kayıt yoksa null.
     * consume() ve peek() ortak kullanır (tek merkez).
     */
    private function rowToArray(mixed $row): ?array
    {
        if (is_array($row)) {
            return $row;
        }
        if (is_object($row)) {
            return method_exists($row, 'toArray') ? $row->toArray() : (array) $row;
        }
        return null;
    }

    /** Token'ın geçerli olup olmadığını **tüketmeden** kontrol eder (sayfa gösterimi için). */
    public function peek(string $rawToken, string $purpose): ?array
    {
        $model = $this->model('project.userToken');
        if (!$model || $rawToken === '') {
            return null;
        }

        $hash = hash('sha256', $rawToken);
        $row = $this->rowToArray($model->where('token_hash', $hash)->first());
        if ($row === null || ($row['purpose'] ?? '') !== $purpose) {
            return null;
        }
        if (!empty($row['used_at']) || strtotime((string) ($row['expires_at'] ?? '')) <= time()) {
            return null;
        }

        return $row;
    }

    /* ==========================================================================
       [ MAINTENANCE ] 🧹
       ========================================================================== */

    /**
     * Kullanıcının o amaçtaki bekleyen token'larını iptal eder (iptal = `used_at` mührü).
     */
    public function revokePending(int $userId, string $purpose): int
    {
        $model = $this->model('project.userToken');
        if (!$model) {
            return 0;
        }

        return (int) $model->where('user_id', $userId)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->update(['used_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Süresi dolmuş satırları temizler. `create()` üzerinden **zaman aşımı eski
     * satırları da mühürler**; bu metot yalnız tablo şişmesini önler.
     */
    public function purgeExpired(int $graceHours = 72): int
    {
        $model = $this->model('project.userToken');
        if (!$model) {
            return 0;
        }

        return (int) $model->where('expires_at', '<', date('Y-m-d H:i:s', time() - ($graceHours * 3600)))
            ->delete();
    }
}