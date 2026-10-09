<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\RbnSuite\RbnAuth\Middleware;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Bundles\RbnSuite\RbnAuth\Handlers\SessionHandler;
use Rbn\Framework\Bundles\RbnSuite\RbnAuth\Support\RememberTokenService;
use Rbn\Framework\Core\Http\Security\AuthPolicy;

/**
 * AuthMiddleware - The Unified Authentication & Security Sentinel 🛡️🗝️⚔️
 * RBN Framework Standard.
 * 
 * Tüm route güvenlik adımlarını (Remember-Me, Login Guard, Hijack Guard, Lockscreen ve Rol Yetkilendirme)
 * tek bir merkezi orkestrasyon altında birleştirir.
 * 
 * @property \Rbn\Framework\Bundles\RbnSuite\RbnAuth\Services\AuthService $authService
 * @property \Rbn\Framework\Bundles\RbnSuite\RbnAuth\Handlers\SessionHandler $sessionHandler
 * @property \Rbn\Framework\Core\Database\Models\Project\UserSecurityModel $userSecurityModel
 */
class AuthMiddleware extends BaseComponent
{
    /**
     * Executes the comprehensive authentication and authorization lifecycle 🚀
     * 
     * @param string|null $requiredRole Optional required role (e.g. 'admin', 'developer', 'user')
     */
    public function handle(?string $requiredRole = null): void
    {
        $auth = $this->authService ?? $this->service('auth');

        // 🎼 Step 1: Session Persistence via Remember-Me Cookie 🍪
        $this->ensureSessionPersistence();

        // 🎼 Step 2: Login Check (Authentication Guard) 🔐
        if (!$auth || !$auth->check()) {
            $this->rejectUnauthenticated();
        }

        // Kullanıcı paneli (`/user/*`) oturum süreleri: sunucu tarafı boşta + mutlak.
        if (self::isUserPanelPath((string) $this->request->path())) {
            $this->enforceUserSessionLifetime($auth);
        }

        // 🎼 Step 3: Anti-Hijacking Fingerprint Guard (IP & User-Agent) 🛡️
        $this->verifyFingerprint($auth);

        // 🎼 Step 4: Lockscreen Enforcement 🔒
        $this->enforceLockscreen();

        // 🎼 Step 5: Hierarchical Role & Permission Check ⚔️
        if (!empty($requiredRole)) {
            $access = $this->handler('access');
            if (!$access || !$access->can($requiredRole)) {
                shield()->forbidden("Bu sayfayı görüntülemek için yetkiniz bulunmamaktadır.");
            }
        }
    }

    /**
     * Step 1 Helper: Ensures session continuity via remember-me token
     */
    private function ensureSessionPersistence(): void
    {
        $auth = $this->authService ?? $this->service('auth');
        if ($auth && $auth->check()) {
            return;
        }

        $token = $_COOKIE['rbn_remember'] ?? null;
        if (!is_string($token) || $token === '') {
            return;
        }

        // 🔐 [A-10 · SUNUCU TARAFI SÜRE + İMZA KAPISI] Çerez ömrüne GÜVENİLMEZ.
        // ÖNCEKİ HALİ: yalnız DB'de hash karşılaştırması yapılıyordu; token
        // süresiz olduğu için "süre doldu" diye bir kavram yoktu. Artık imza
        // (bitiş zamanı kurcalanamaz) ve süre sunucu tarafında denetlenir.
        // Reddedilen token için çerez de silinir → kullanıcı temiz girişe düşer.
        if (!RememberTokenService::isAcceptable($token)) {
            $this->clearRememberCookie();
            return;
        }

        $hashedToken = $this->helper('crypto')->hash($token);

        // Check Master Developer
        $master = $this->model('master.developer')->where('dev_token_hash', $hashedToken)->first();
        if ($master) {
            $this->sessionHandler->start($master, true, true);
            return;
        }

        // Check Project User
        $vaultEntry = $this->userSecurityModel->where('remember_token', $hashedToken)->first();
        if ($vaultEntry) {
            // [A-10 · KIRIK YOLUN DÜZELTİLMESİ] Burada `findByIdentity()` çağrılıyordu
            // ve SAYISAL user_id ile e-posta/kullanıcı adı aranıyordu:
            // `UserRepository::findByIdentity()` yalnız `email`/`username` kolonlarına
            // bakar → hiçbir zaman eşleşme yok → NORMAL KULLANICILARDA
            // "beni hatırla" TAMAMEN ÖLÜYDÜ (yalnız master geliştirici çalışıyordu).
            // Doğru yol: KİMLİKTEN değil, `user_id`'den getir.
            $userRepo = $this->repository('project.user');
            $user = $userRepo ? $userRepo->getUser((int) $vaultEntry['user_id']) : null;
            if ($user) {
                $this->sessionHandler->start($user, false, true);
            }
        }
    }

    /**
     * [A-10] Reddedilen/geçersiz remember-me çerezini tarayıcıdan düşürür.
     *
     * Bayraklar `SessionHandler::createRememberMe()` ile Aynı olmalıdır; aksi
     * halde tarayıcı silinmeyen çerezi bir sonraki istekte tekrar gönderir.
     */
    private function clearRememberCookie(): void
    {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

        setcookie('rbn_remember', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        unset($_COOKIE['rbn_remember']);
    }

    /**
     * Kullanıcı oturumu süre denetimi (sunucu tarafı).
     *
     * - Boşta: son istekten bu yana `AuthPolicy::sessionIdleMinutes()` (panel ayarı `security.session_timeout`).
     * - Mutlak: girişten bu yana `AuthPolicy::sessionAbsoluteMinutes()` (varsayılan 12 sa).
     * Süre dolduysa oturum kapatılır (beni-hatırla çerezi dahil) ve `/`a dönülür.
     * Zaman damgası olmayan eski oturuma damga şimdi basılır (geriye uyum).
     */
    private function enforceUserSessionLifetime(mixed $auth): void
    {
        $session = $this->session();
        $now = time();

        $startedAt = (int) $session->get(SessionHandler::STARTED_AT_KEY, 0);
        $lastActivity = (int) $session->get(SessionHandler::LAST_ACTIVITY_KEY, 0);

        if ($startedAt <= 0) {
            $session->set(SessionHandler::STARTED_AT_KEY, $now);
            $startedAt = $now;
        }
        if ($lastActivity <= 0) {
            $lastActivity = $now;
        }

        $idleExpired = ($now - $lastActivity) > AuthPolicy::sessionIdleMinutes() * 60;
        $absoluteExpired = ($now - $startedAt) > AuthPolicy::sessionAbsoluteMinutes() * 60;

        if ($idleExpired || $absoluteExpired) {
            try {
                $this->logs()->channel('auth')->info('USER_SESSION_EXPIRED', [
                    'reason'  => $absoluteExpired ? 'absolute' : 'idle',
                    'user_id' => (int) $session->get('user_id', 0),
                ]);
            } catch (\Throwable) {
                // Log yazılamasa da oturum kapanır.
            }

            $auth->logout();
            $this->rejectUnauthenticated();
        }

        $session->set(SessionHandler::LAST_ACTIVITY_KEY, $now);
    }

    /**
     * Oturumsuz istek: tarayıcı gezintisi `/`'a yönlenir; JSON bekleyen istek
     * (`Accept: application/json` ya da `X-Requested-With: XMLHttpRequest`) 302
     * yerine 401 `UNAUTHENTICATED` alır (istemci giriş HTML'ini JSON diye ayrıştırmaz).
     */
    private function rejectUnauthenticated(): never
    {
        if ($this->request->wantsJson() || $this->request->isAjax()) {
            response()->error('Oturum bulunamadı ya da süresi doldu, lütfen yeniden giriş yapın.', 401, ['code' => 'UNAUTHENTICATED']);
            exit;
        }

        response()->redirect('/');
        exit;
    }

    /**
     * Yol kullanıcı paneli mi? (`/user` ya da `/user/...`; sorgu dizesi atılır.)
     */
    public static function isUserPanelPath(string $path): bool
    {
        $path = strtolower(trim(substr($path, 0, strcspn($path, '?#')), '/'));

        return $path === 'user' || str_starts_with($path, 'user/');
    }

    /**
     * Step 3 Helper: Anti-Hijacking Fingerprint Verification
     */
    private function verifyFingerprint(mixed $auth): void
    {
        $currentIp = $this->request->ip() ?? ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $currentUa = $this->request->userAgent() ?? ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown');
        $expectedFingerprint = hash('sha256', $currentIp . '|' . $currentUa);

        $savedFingerprint = $this->session()->get('auth_fingerprint');

        if ($savedFingerprint && !hash_equals((string)$savedFingerprint, $expectedFingerprint)) {
            $auth->logout();
            $this->rejectUnauthenticated();
        }
    }

    /**
     * Step 4 Helper: Enforce Lockscreen when session is locked
     */
    private function enforceLockscreen(): void
    {
        $isLocked = (bool) $this->session()->get('is_locked', false);
        if (!$isLocked) {
            return;
        }

        // [A-15] Kilitliyken POST yalnız kilit açma uçlarına (giriş/doğrulama) izinli;
        // aksi halde kilitli oturum yazma işlemlerini sürdürebiliyordu. Bu denetim
        // aşağıdaki alt dizge listesinden ÖNCE yapılır (yolda "login" geçen herhangi
        // bir POST o listeden geçip kilidi atlıyordu).
        if ($this->request->isPost()) {
            if (self::lockedPostAllowed((string) $this->request->path())) {
                return;
            }

            shield()->forbidden('Oturum kilitli. İşlem için önce kilidi açın.');
            exit;
        }

        $currentUrl = $this->request->url();
        $excludedPaths = ['lockscreen', 'login', 'logout', 'authenticate'];

        foreach ($excludedPaths as $path) {
            if (stripos($currentUrl, $path) !== false) {
                return;
            }
        }

        $this->Route->redirect($this->Route->url('lockscreen'));
        exit;
    }

    /**
     * Kilitli oturumda izin verilen POST yolları (kilit açma akışı): giriş formu
     * (`auth/login`) ve doğrulama (`auth/authenticate`). Yol normalize edilir
     * (sorgu, `//`, `\`, `%2e%2e`, `..`, büyük/küçük harf); alt dizge değil TAM eşleşme.
     */
    public static function lockedPostAllowed(string $path): bool
    {
        $path = substr($path, 0, strcspn($path, '?#'));
        $path = str_replace('\\', '/', rawurldecode($path));

        $segments = [];
        foreach (explode('/', strtolower($path)) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($segments);
                continue;
            }
            $segments[] = $segment;
        }

        return in_array('/' . implode('/', $segments), ['/auth/login', '/auth/authenticate'], true);
    }
}
