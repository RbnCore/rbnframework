<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\RbnSuite\RbnAuth\Services;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Bundles\RbnSuite\RbnAuth\Models\AuthRole;

/**
 * AuthService - The Sovereign Entryway & Identity Authority 🎻🏹⚓
 * RBN 3.5 Masterpiece Standard.
 * 
 * The primary Chef for the RbnAuth Gateway.
 * Orchestrates login, logout, identity checks, and authentication state (SSoT).
 * 
 * @property \Rbn\Framework\Bundles\RbnSuite\RbnAuth\Handlers\AccessHandler $accessHandler
 * @property \Rbn\Framework\Bundles\RbnSuite\RbnAuth\Handlers\SessionHandler $sessionHandler
 * @property \Rbn\Framework\Core\Database\Repositories\Project\UserRepository $userRepository
 */
class AuthService extends BaseService
{
    /**
     * Kullanıcının oturumu açık mı kontrol eder 🔐
     */
    public function check(): bool
    {
        return (bool) $this->session()->get('is_logged_in', false);
    }

    /**
     * Attempts to securely open the gateway 🔑🛰️⚓
     * RBN 3.5 Universal Security: Supports Local and Master Developer access.
     */
    public function login(string $identity, string $password, bool $remember = false): array
    {
        $userRepo = $this->repository('project.user');

        // 🎼 Step 1: Attempt Master Developer Entry (Cross-Project Authority) 👨‍💻🌍🏹
        $master = $userRepo ? $userRepo->findMasterDeveloper($identity) : null;

        if ($master) {
            // 🎼 RBN 3.5: Master Developer Password Verification 🔐🛰️
            $passwordHash = $master['password'] ?? null;
            $crypto = $this->helper('crypto');

            if ($passwordHash && $crypto->verify($password, $passwordHash)) {
                $this->sessionHandler->start($master, true, $remember);
                $this->handler('audit')->logSuccess((int) $master['id'], "MASTER::{$identity}", 'master');

                $projectKey = function_exists('project_key') ? project_key() : '';
                $adminPanelDisabled = (bool) $this->resolveProjectData('admin_panel_disabled', $projectKey);

                if ($adminPanelDisabled) {
                    $this->session()->set('user_role', 'user');

                    $displayName = $master['full_name'] ?? $master['username'] ?? 'Kullanıcı';
                    return [
                        'success' => true,
                        'message' => "👋 Hoş geldin, {$displayName}!",
                        'redirect' => '/user'
                    ];
                }

                return [
                    'success' => true,
                    'message' => "👑 Hoş geldin, " . ($master['full_name'] ?? 'Hükümdar Geliştirici') . "!",
                    'redirect' => $this->Route->url('', [], 'admin')
                ];
            }
        }

        // 🎼 Step 2: Conventional Entry via AccessHandler 🛡️⚓
        $accessHandler = $this->accessHandler ?? $this->handler('access');
        $result = $accessHandler ? $accessHandler->authenticate($identity, $password) : ['success' => false, 'message' => 'Erişim servisine ulaşılamadı.'];

        if (!$result['success']) {
            return $result;
        }

        // 🎼 Step 3: Persistence layer initialization 💎
        $user = $result['user'];
        $this->sessionHandler->start($user, false, $remember);
        if ($userRepo) {
            $userRepo->setOnlineStatus((int) $user['id'], true);
        }

        // 🎼 Step 4: Trait-Only Dynamic Project Guard (Zero DB/Provider Overhead) 🎻🎭
        $projectKey = function_exists('project_key') ? project_key() : '';
        $requiredRole = (string) ($this->resolveProjectData('required_login_role', $projectKey) ?? '');

        $role = $user['role'] ?? 'user';
        $roleInfo = AuthRole::ROLES[$role] ?? [];
        $icon = $roleInfo['icon'] ?? '🛡️';
        $roleName = $roleInfo['name'] ?? 'Kullanıcı';

        // 🛑 CONDITION 0: Minimum Required Role Guard (e.g. required_login_role = 'developer')
        if (!empty($requiredRole)) {
            $roles = AuthRole::ROLES;
            $userLevel = $roles[$role]['level'] ?? 0;
            $requiredLevel = $roles[$requiredRole]['level'] ?? 0;

            if ($userLevel < $requiredLevel) {
                $this->logout();

                return [
                    'success' => false,
                    'message' => 'Bu yönetim paneline erişim yetkiniz bulunmamaktadır.',
                ];
            }
        }

        $adminPanelDisabled = (bool) $this->resolveProjectData('admin_panel_disabled', $projectKey);

        // 🛑 CONDITION 1: Admin Panel Disabled -> Force 'user' role & /user redirect
        if ($adminPanelDisabled) {
            $this->session()->set('user_role', 'user');

            $displayName = $user['full_name'] ?? $user['name'] ?? $user['username'] ?? 'Kullanıcı';
            return [
                'success' => true,
                'message' => "👋 Hoş geldin, {$displayName}!",
                'redirect' => '/user'
            ];
        }

        // 🚀 CONDITION 2: Admin Panel Enabled (admin_panel_disabled = 0/absent) -> Smart Role Routing
        $isAdmin = in_array($role, AuthRole::ADMIN_ROLES);

        if ($isAdmin) {
            return [
                'success' => true,
                'message' => "{$icon} Hoş geldin, " . ($user['username'] ?? 'User') . "! ($roleName Yetkisiyle)",
                'redirect' => $this->Route->url('', [], 'admin')
            ];
        }

        $this->session()->set('user_role', 'user');

        $displayName = $user['full_name'] ?? $user['name'] ?? $user['username'] ?? 'Kullanıcı';
        return [
            'success' => true,
            'message' => "👋 Hoş geldin, {$displayName}!",
            'redirect' => '/user'
        ];
    }

    /**
     * Oturumdaki kullanıcı verilerini döner 👤
     */
    public function user(): ?array
    {
        if (!$this->check()) {
            return null;
        }

        $session = $this->session();

        return [
            'id' => $session->get('user_id'),
            'role' => $session->get('user_role'),
            'name' => $session->get('user_name'),
            'username' => $session->get('user_username'),
            'email' => $session->get('user_email'),
            'profile_image' => $session->get('user_profile_image'),
            'is_master' => (bool) $session->get('is_master_developer', false)
        ];
    }

    /**
     * Securely exits the gateway 🛡️🚪
     */
    public function logout(): bool
    {
        // 🎼 Step 1: Update Online Status (Leaving) 🚥
        $userId = $this->session()->get('user_id');
        $userRepo = $this->repository('project.user');
        if ($userId && $userRepo) {
            $userRepo->setOnlineStatus((int) $userId, false);
        }

        // 🎼 Step 2: Secure termination via AccessHandler 🗝️🛡️
        return $this->accessHandler ? $this->accessHandler->terminate() : true;
    }
}
