<?php

namespace Rbn\Framework\Core\Http\Engine\Traits\Alert;

use Rbn\Framework\Core\Http\Engine\RequestEnvironment;

/**
 * StorageTrait - The Flash Memory 💾🛡️
 */
trait StorageTrait
{
    /** @var string Primary storage mode (Session/Cookie/Both) */
    protected string $storageMode = 'session'; 

    /** @var bool Whether to force cookie storage */
    protected bool $useCookie = true;

    /**
     * Force the alert to be stored in a Cookie (for JS consumption). 🛡️🍪
     */
    public function viaCookie(): self
    {
        $this->useCookie = true;
        return $this;
    }

    /**
     * Force the alert to be stored in the Session only. 🔑
     */
    public function viaSession(): self
    {
        $this->useCookie = false;
        $this->storageMode = 'session';
        return $this;
    }

    /**
     * Store the alert data in the chosen storage engine. 🚀
     */
    protected function storeAlert(array $alertData): void
    {
        // 1. Session Storage (Primary)
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['_rbn_alert'] = $alertData;
        }

        // 2. Cookie Storage (Optional or Forced)
        if ($this->useCookie) {
            $json = json_encode($alertData, JSON_UNESCAPED_UNICODE);
            $this->writeAlertCookie($json, time() + 60);
        }
    }

    /* =========================================================================
     | [GÜVENLİK · http #11] `rbn_alert` çerezi bayraksız üretiliyordu.
     |
     | `HttpOnly` BİLEREK YOK: `Resources/Assets/RbnCommon/js/components/rbnAlert.js`
     | çereyi `document.cookie` üzerinden okuyor (satır ~435) — `HttpOnly`
     | konulursa uyarı hiç gösterilemez. Kanıt: bu dosyadaki JS okuyucu.
     | Kalan bayraklar: HTTPS'te `Secure` (A0-4 ile aynı kaynak) ve
     | `SameSite=Lax` (çapraz-site istek çerezi göndermezsin).
     * ========================================================================= */
    private function writeAlertCookie(string $value, int $expires): void
    {
        setcookie('rbn_alert', $value, [
            'expires'  => $expires,
            'path'     => '/',
            'domain'   => '',
            'secure'   => RequestEnvironment::isHttpsRequest(),
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
    }

    /**
     * Clear all stored alerts. 🧹
     */
    public function clear(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION['_rbn_alert']);
        }

        if (isset($_COOKIE['rbn_alert'])) {
            // Silme çerezi ÜRETEN çerezle AYNI bayrakları taşımalı: tarayıcı
            // (özellikle `Secure`) eşleşmeyen bir silmeyi yok sayabilir.
            $this->writeAlertCookie('', time() - 3600);
        }
    }
}
