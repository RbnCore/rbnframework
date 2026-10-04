<?php

namespace Rbn\Framework\Bundles\RbnSuite\RbnAuth\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Bundles\RbnSuite\RbnAuth\Support\RememberTokenService;

/**
 * SessionHandler - The Session Mutation Worker 🛡️🗝️⚓
 * RBN 3.5 Masterpiece Standard.
 * 
 * Specialized execution unit for session storage mutation
 * and remember-me token lifecycle management.
 * 
 * @property \Rbn\Framework\Core\Database\Models\Project\UserSecurityModel $userSecurityModel
 */
class SessionHandler extends BaseComponent
{
    /**
     * Starts a secure authentication session 🚀
     */
    public function start($user, bool $isMaster = false, bool $remember = false): void
    {
        // 🔐 [GÜVENLİK YAMASI · A0-4] OTURUM SABİTLEME (session fixation) KAPANDI.
        //
        // ÖNCEKİ HALİ: `session_regenerate_id(true)` framework'ın HİÇBİR YERİNDE
        // çağrılmıyordu (ölçüldü: 0 çağrı). Yani kimlik doğrulaması başarılı
        // olsa bile oturum ID'si **giriş öncesiyle aynı kalıyordu**. Saldırgan
        // kurbanın tarayıcısına kendi seçtiği bir oturum ID'si yapıştırır
        // (A0-4 / ONARIM-PLANI §2, A-09: "metot var, çağıran 0").
        //
        // KURAL: kimlik değişimi **her zaman**, `start()`'ın EN BAŞINDA olur —
        // kullanıcı verisi veya remember-me çerezi yazılmadan ÖNCE. Çünkü
        // `session_regenerate_id(true)` eski oturum DOSYASINI siler; sonra
        // yazılsaydı değerler eski dosyada kalır ve oturum boş doğardı.
        //
        // `true` = ESKİ OTURUM DOSYASI SİLİNSİN. Bu, "eski ID ile yeni oturum
        // aynı anda geçerli" penceresini de kapatır.
        //
        // [A0-4 BAĞIMLILIK RİSKİ — kapatıldı] `regenerate` eski oturumu siler;
        // bu yüzden remember-me çerezi yeniden ÜRETİLMELİ, yoksa 1 giriş sonra
        // "beni hatırla" düşer. Aşağıda bu yüzden `createRememberMe()` çağrısı
        // yenileme sonrasına ve **yeni oturum ID'si üzerinden** yapılır.
        $this->regenerateSessionId();

        if ($isMaster) {
            $userRole = 'developer';
            $userName = $user['full_name'] ?? 'Hükümdar Geliştirici';
            $userUsername = $user['username'] ?? 'developer';
            $userEmail = $user['email'] ?? '';
        } else {
            $userRole = $user['role'] ?? 'user';
            $userName = $user['name'] ?? ($user['username'] ?? 'User');
            $userUsername = $user['username'] ?? 'user';
            $userEmail = $user['email'] ?? '';
        }

        // 🛡️ RBN 3.5 Sovereign Fingerprint: IP & User-Agent Binding (Anti-Session Hijacking)
        $ip = $this->request->ip() ?? ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $ua = $this->request->userAgent() ?? ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown');
        $fingerprint = hash('sha256', $ip . '|' . $ua);

        $session = $this->session();
        $session->set('user_id', $user['id']);
        $session->set('user_role', $userRole);
        $session->set('user_name', $userName);
        $session->set('user_username', $userUsername);
        $session->set('user_email', $userEmail);
        $session->set('user_profile_image', $user['profile_image'] ?? null);
        $session->set('is_logged_in', true);
        $session->set('is_master_developer', $isMaster);
        $session->set('is_locked', false);
        $session->set('auth_fingerprint', $fingerprint);

        if ($remember) {
            $this->createRememberMe((int)$user['id'], $isMaster);
        }
    }

    /**
     * 🔐 [A0-4] Oturum kimliğini yeniler (session fixation).
     *
     * - Oturum HENÜZ başlamadıysa (`PHP_SESSION_NONE`) hiçbir şey yapılmaz:
     *   yabancı bir çerezle oturum açma denemesinde yenilenecek oturum yoktur.
     * - `session_regenerate_id(true)` başarısız olursa **fail-closed**: eski ID
     *   kullanılmaya devam edilmez, oturum düşürülür. Aksi halde saldırganın
     *   sabitlediği ID geçerli kalırdı.
     *
     * @return bool kimlik yenilendiyse true
     */
    private function regenerateSessionId(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            return false;
        }

        // Önceki oturumda kimlik doğrulama öncesi yazılmış alanları temizle ki
        // yenileme sonrası "misafir" veri taşınmasın.
        $_SESSION = [];

        if (session_regenerate_id(true)) {
            return true;
        }

        // Fail-closed: yenilenemediyse oturumu düşür.
        $this->destroySessionFallback();

        return false;
    }

    /**
     * 🔐 [A0-4 · fail-closed] Yenileme başarısızsa oturumu düşür.
     *
     * `session_destroy()` + çerez temizliği. Amaç: sabitlenmiş ID'nin geçerli
     * kalmasını engellemek; kullanıcı bir sonraki istekte yeni oturum alır.
     */
    private function destroySessionFallback(): void
    {
        try {
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION = [];
                setcookie(session_name(), '', [
                    'expires'  => time() - 42000,
                    'path'     => '/',
                    'httponly' => true,
                    'samesite' => 'Lax',
                    'secure'   => (string) ($_SERVER['HTTPS'] ?? '') !== ''
                        && strtolower((string) $_SERVER['HTTPS']) !== 'off',
                ]);
                session_destroy();
            }
        } catch (\Throwable $e) {
            // Oturum düşürülemediyse de kimlik yenilenmedi; çağıran taraf
            // oturumu kimliksiz kabul edip yazma yapmaz.
        }
    }

    /**
     * Locks the current session for security 🔒
     *
     * [YA-9 · DÜZELTME] TEK YAZMA YOLU.
     *
     * ÖNCEKİ HALİ iki yazıyordu:
     *   $_SESSION['is_locked'] = true;                       // doğrudan süperglobal
     *   $this->storage->sessions()->set('is_locked', true); // oturum katmanı
     * Ölçüldü (`Core/System/Storage/Providers/SessionProvider.php:139,160`):
     * `get()`/`set()` **doğrudan `$_SESSION`** dizisini okuyup yazıyor; yani iki
     * satır AYNI anahtara gidiyordu. Sonuç: iki kaynak izlenimi; hangisinin
     * okuyucu olduğunu ayırmak gereksiz. Okuyan taraf tek kaynaktadır:
     * `AuthMiddleware::enforceLockscreen()` → `$this->session()->get('is_locked')`.
     * Davranış DEĞİŞMEDİ: bayrak yine `$_SESSION['is_locked']` = true olur
     * (birim testi `fw_rbnauth_giris_hata_sizintisi.php` bunu doğrular).
     */
    public function lock(): void
    {
        $this->session()->set('is_locked', true);
    }

    /**
     * Creates a long-lived remember-me token 🛡️⚓
     *
     * [A0-4 BAĞIMLILIK RİSKİ — NEDEN BURADA?] `start()` artık önce
     * `regenerateSessionId()` çağırıyor ve `createRememberMe()` **sonra** çalışıyor.
     * Sıralama tersine çevrilirse `session_regenerate_id(true)` eski oturumu
     * siler, remember-me çerezi eski oturuma bağlanmış kalır ve kullanıcı
     * "1 giriş sonra hatırlanmıyorum" hatasını görür. Bu yüzden çağrı yeri
     * değiştirilmemeli; token her zaman YENİLENMİŞ oturumdan sonra yazılır.
     *
     * [A-10] Token artık **süreli ve imzalı**: `RememberTokenService::issue()`
     * bitiş zamanını token'ın içine gömer (süre: sabit 30 gün, TEK kaynak).
     * ÖNCEKİ HALİ süreyi YALNIZ çerez `expires` alanına yazıyordu; sunucu
     * tarafında süre denetimi yoktu (çerez uzatılsa bile token geçerliydi).
     * ŞEMA DEĞİŞMEDİ: DB'de yine `remember_token` / `dev_token_hash` özeti.
     */
    protected function createRememberMe(int $userId, bool $isMaster = false): void
    {
        // [A-10 · fail-closed ama girişi BOZMA] Uygulama anahtarı okunamazsa
        // imza üretilemez. O durumda hatırlamak yerine "hatırla" YOK sayılır:
        // giriş normal şekilde tamamlanır, yalnız çerez yazılmaz.
        try {
            $issued = RememberTokenService::issue();
        } catch (\Throwable $e) {
            error_log('RBN Guvenlik: remember-me tokeni uretilemedi (uygulama anahtari cozulemedi), oturum hatirlama kapali. ' . $e->getMessage());
            return;
        }

        $token = $issued['token'];
        $hashedToken = $this->helper('crypto')->hash($token);

        // 🎼 Step 1: Master Developer Guard 👑
        if ($isMaster) {
            $masterModel = $this->model('master.developer');
            $masterModel->update($userId, [
                'dev_token_hash' => $hashedToken,
            ]);
        } else {
            // 🎼 Step 3: Update local project security registry (Smart Persistence) 🕵️‍♂️📜
            $security = $this->userSecurityModel->find($userId);

            if ($security) {
                $this->userSecurityModel->update($userId, [
                    'remember_token' => $hashedToken,
                ]);
            } else {
                $this->userSecurityModel->create([
                    'user_id'        => $userId,
                    'remember_token' => $hashedToken,
                ]);
            }
        }

        // 🎼 Step 4: Secure Cookie Deployment 📦
        // [A-10] Çerez ömrü artık tokenın İMZALI bitiş zamanıyla AYNI kaynaktan
        // gelir (`RememberTokenService::MAX_AGE_SECONDS` = 30 gün, TEK yer).
        // `settings.security.remember_me_duration` ARTARAK kullanılmaz: iki
        // ayrı süre kaynağı, "çerez 30 gün ama sunucu 90 gün" gibi sessiz
        // tutarsızlık üretirdi. (Bkz. .github/UPGRADING.md.)
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
        setcookie('rbn_remember', $token, [
            'expires'  => $issued['expires_at'],
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
}
