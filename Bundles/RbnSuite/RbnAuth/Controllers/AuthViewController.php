<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\RbnSuite\RbnAuth\Controllers;

use Rbn\Framework\Core\Base\Web\BaseController;
use Rbn\Framework\Bundles\RbnSuite\RbnAuth\Models\AuthRole;

/**
 * AuthViewController - Visual Presentation Controller for RbnAuth 🎨🏰⚓
 * RBN Framework Standard.
 * 
 * @property \Rbn\Framework\Bundles\RbnSuite\RbnAuth\Services\AuthService $authService
 * @property \Rbn\Framework\Bundles\RbnSuite\RbnAuth\Services\RecoveryService $recoveryService
 */
class AuthViewController extends BaseController
{
    /**
     * Login - Giriş Yap Sayfası
     */
    public function showLogin(): void
    {
        $session = $this->session();

        if ($session->get('is_logged_in')) {
            $role = (string) $session->get('user_role', 'user');
            $this->Route->redirect($this->getDashboardUrl($role));
            return;
        }

        // 🎼 Dynamic Admin Prefix & LOGIN_PATH Detection (rbn-admin & dashPrefix) 🛡️
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $cleanUri = '/' . trim((string) parse_url($uri, PHP_URL_PATH), '/');

        $dashPrefix = function_exists('project_data') ? project_data('dashboard_prefix') : null;
        $dashPrefix = !empty($dashPrefix) ? trim((string) $dashPrefix, '/') : null;
        $loginPath = \Rbn\Framework\Core\Support\Definitions\Route\RouteBlueprint::LOGIN_PATH ?? 'rbn-admin';

        $isAdminLogin = str_starts_with($cleanUri, '/' . ltrim($loginPath, '/'))
            || ($dashPrefix && $dashPrefix !== 'user' && str_starts_with($cleanUri, '/' . $dashPrefix));

        if ($isAdminLogin) {
            // FW-ROBOTS-TAKIP-109: gizli giris yolu (`LOGIN_PATH`) robots.txt'te
            // ILAN EDILMEZ; dizinlenmeme bu header + meta ile saglanir.
            $this->noIndex('noindex, nofollow');
            $this->render('auth', 'login');
            return;
        }

        // Son Kullanıcı Portalı: Anasayfaya (/) Yönlendir
        response()->redirect('/');
    }

    /**
     * Register - Kayıt Ol Sayfası
     */
    public function showRegister(): void
    {
        $session = $this->session();

        if ($session->get('is_logged_in')) {
            $role = (string) $session->get('user_role', 'user');
            $this->Route->redirect($this->getDashboardUrl($role));
            return;
        }

        $this->render('auth', 'register');
    }

    /**
     * Lockscreen Sayfası
     */
    public function showLockscreen(): void
    {
        $session = $this->session();

        // 🎼 RBN Framework: Security Guard 🛡️
        if ($session->get('is_logged_in') && !$session->get('is_locked')) {
            $action = $this->request->query('action');

            if ($action === 'lock') {
                $session->set('is_locked', true);
            } else {
                // 🚀 Manual navigation? Go back to Dashboard.
                $role = (string) $session->get('user_role', 'user');
                $this->Route->redirect($this->getDashboardUrl($role));
                return;
            }
        }

        // 🎼 RBN Framework: Identity Persistence Check
        $auth = $this->authService ?? $this->service('auth');
        $user = $auth ? $auth->user() : null;

        if (!$user) {
            $this->Route->redirect('login');
            return;
        }

        $lockData = [
            'user' => [
                'name' => $user['name'] ?? 'Kullanıcı',
                'email' => $user['email'] ?? '',
                'avatar' => $user['profile_image'] ?? null
            ]
        ];

        $this->render('auth', 'lockscreen', $lockData);
    }

    /**
     * Şifre Sıfırlama Sayfası
     */
    public function showForgotPassword(): void
    {
        if ($this->session()->get('is_logged_in')) {
            $this->Route->redirect('/');
            return;
        }
        $this->render('auth', 'forgot-password');
    }

    /**
     * Kod Doğrulama Sayfası
     *
     * [A0-6] Ölü akış kapatıldı. Bu sayfa `reset_step` oturum değişkenini bekliyordu;
     * o değişkeni framework'ta **yazan kod yok** (grep 0) → sayfa her istekte
     * `/forgot-password`'a geri dönüyordu (A-03/YA-1). Artık akış **tek bağlantı**
     * (token) üzerinden yürür; 6 haneli kod üretilmez.
     */
    public function showVerifyCode(): void
    {
        $this->Route->alert(
            'error',
            'Kod ile doğrulama kaldırıldı. Şifre sıfırlama artık e-postanızdaki güvenli bağlantı ile yapılır.',
            '/forgot-password'
        );
    }

    /**
     * Şifre Yenileme Sayfası
     *
     * [A0-6] Sayfa artık `reset_step` oturum değişkeni yerine **gerçek bir token**
     * ister: `GET /reset-password?token=<token>`. Token yoksa/geçersizse/süresi
     * dolmuşsa form **gösterilmez** ve kullanıcı "yeni bağlantı iste" sayfasına
     * gider. Token burada **tüketilmez** — tüketim `POST` aşamasında olur
     * (tek kullanımlık: formu açmak token'ı harcamaz, göndermek harcar).
     */
    public function showResetPassword(): void
    {
        $token = trim((string) ($_GET['token'] ?? ''));

        if ($token === '' || !$this->recoveryService->canShowResetForm($token)) {
            $this->Route->alert(
                'error',
                'Şifre sıfırlama bağlantısı eksik, geçersiz veya süresi dolmuş. Lütfen yeni bir bağlantı isteyin.',
                '/forgot-password'
            );
            return;
        }

        $this->render('auth', 'reset-password', ['token' => $token]);
    }

    /**
     * Kullanıcı Dashboard Sayfası 👤
     */
    public function showUserDashboard(?string $path = null): mixed
    {
        $session = $this->session();

        // 🛡️ 1. Projeye Özel `user_dash_controller` (project-routemap.php SSoT)
        $userDash = $this->resolveProjectData('user_dash_controller') ?? $this->getRouteConfig(project_key() ?: '', 'user_dash_controller');
        if (!empty($userDash) && is_string($userDash)) {
            [$ctrlRef, $method] = explode('@', $userDash . '@index');
            $actionMethod = $path ? 'handle' . ucfirst($path) : ($path ?: $method);

            // A. Doğrudan IoC veya FQCN Çözümleme
            $ctrlInstance = $this->controller($ctrlRef);
            if (!$ctrlInstance && class_exists($ctrlRef)) {
                $ctrlInstance = new $ctrlRef();
            }
            if (!$ctrlInstance) {
                $cleanRef = ltrim($ctrlRef, '\\');
                $fqcn = "\\Rbn\\Project\\Modules\\" . $cleanRef;
                if (class_exists($fqcn)) {
                    $ctrlInstance = new $fqcn();
                }
            }

            if ($ctrlInstance) {
                $targetMethod = method_exists($ctrlInstance, $actionMethod) ? $actionMethod : $method;
                if (method_exists($ctrlInstance, $targetMethod)) {
                    return $ctrlInstance->$targetMethod();
                }
            }
        }

        $userId = $session->get('user_id');
        $userName = $session->get('user_name') 
            ?? $session->get('username') 
            ?? 'Kullanıcı';

        $userRole = (string) $session->get('user_role', 'user');

        $this->noIndex('noindex, nofollow');

        $domainName = (string) $this->resolveProjectData('domain');
        $projectKey = (string) ($this->resolveProjectData('project_key') ?? 'proje');
        $customPath = (string) ($this->resolveProjectData('custom_path') ?? $projectKey);
        $pageTitle  = "User Dashboard | " . strtoupper((string)$domainName);
        $pageDesc   = "Sisteme rbnAuth doğrulaması ile başarıyla giriş yapıldı (Rol: " . strtoupper($userRole) . ").";

        $data = [
            'no_navbar'  => true,
            'no_footer'  => true,
            'userId'     => $userId,
            'userName'   => $userName,
            'userRole'   => $userRole,
            'userPath'   => $path,
            'domainName' => $domainName,
            'projectKey' => $projectKey,
            'customPath' => $customPath,
            'pageTitle'  => $pageTitle,
            'pageDesc'   => $pageDesc,
        ];

        // 🛡️ Framework'ün Ortak RbnCommon/userdash Şablonunu Bas!
        $this->context = 'auth';
        return $this->render('RbnCommon/userdash', $data);
    }

    /**
     * Rol bazlı dinamik dashboard URL'ini belirler 🎯⚖️
     */
    private function getDashboardUrl(string $role): string
    {
        $isAdmin = in_array($role, AuthRole::ADMIN_ROLES);
        return $isAdmin ? $this->Route->url('', [], 'admin') : '/user';
    }
}
