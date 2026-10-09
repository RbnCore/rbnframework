<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\System\Managers;

use Rbn\Framework\Core\Base\Services\BaseManager;

/**
 * SessionManager - Global Session Lifecycle & State Orchestrator 🔑🏛️⚓
 * RBN Framework Standard.
 * 
 * Sorumluluk: Oturum verisi, kullanıcı kimliği, flash mesajları ve oturum sonlandırmayı
 * Storage katmanından bağımsız olarak tek bir merkezden (SSoT) yönetir.
 */
class SessionManager extends BaseManager
{
    /**
     * Oturumun açık ve kullanılabilir olduğundan emin olur 🛡️
     */
    protected function ensureStarted(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
    }

    /**
     * Session'dan anahtar oku 🔑
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->ensureStarted();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Session'da anahtar var mı kontrol et 🔍
     */
    public function has(string $key): bool
    {
        $this->ensureStarted();
        return isset($_SESSION[$key]);
    }

    /**
     * Session'a veri yaz 📝
     */
    public function set(string $key, mixed $value): bool
    {
        $this->ensureStarted();
        $_SESSION[$key] = $value;
        return true;
    }

    /**
     * Session'dan anahtar sil 🗑️
     */
    public function delete(string $key): bool
    {
        $this->ensureStarted();
        unset($_SESSION[$key]);
        return true;
    }

    /**
     * Tek seferlik Flash mesajı ekle ⚡
     */
    public function flash(string $key, mixed $value): bool
    {
        $this->ensureStarted();
        $_SESSION['_rbn_flash'][$key] = $value;
        return true;
    }

    /**
     * Tek seferlik Flash mesajını oku ve sil ⚡
     */
    public function getFlash(string $key, mixed $default = null): mixed
    {
        $this->ensureStarted();
        if (isset($_SESSION['_rbn_flash'][$key])) {
            $val = $_SESSION['_rbn_flash'][$key];
            unset($_SESSION['_rbn_flash'][$key]);
            return $val;
        }
        return $default;
    }

    /**
     * Tüm session verilerini dizi olarak döner 📦
     */
    public function all(): array
    {
        $this->ensureStarted();
        return $_SESSION ?? [];
    }

    /**
     * Oturum ID'sini yeniler (Session Fixation Koruması) 🔒
     */
    public function regenerate(bool $deleteOldSession = true): bool
    {
        $this->ensureStarted();
        return @session_regenerate_id($deleteOldSession);
    }

    /**
     * Oturumu tamamen sonlandırır ve temizler (Logout) 🚪🧹
     */
    public function end(): bool
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            @session_unset();
            @session_destroy();
        }

        // Session cookie'sini de düşür
        if (isset($_COOKIE[session_name()])) {
            // Silme çerezi, oturum çereziyle AYNI bayraklarla gider
            // (`SessionSandboxStage`: Secure yalnız HTTPS'te, HttpOnly, SameSite=Lax).
            setcookie(session_name(), '', [
                'expires'  => time() - 3600,
                'path'     => '/',
                'secure'   => \Rbn\Framework\Core\Http\Engine\RequestEnvironment::isHttpsRequest(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            unset($_COOKIE[session_name()]);
        }

        return true;
    }
}
