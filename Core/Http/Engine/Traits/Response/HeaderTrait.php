<?php

namespace Rbn\Framework\Core\Http\Engine\Traits\Response;

use Rbn\Framework\Core\Http\Engine\RequestEnvironment;

/**
 * HeaderTrait - The HTTP Protocol Negotiator ⚓🛡️
 */
trait HeaderTrait
{
    /**
     * Set the HTTP response status code.
     */
    public function status(int $code): self
    {
        if (!headers_sent()) {
            http_response_code($code);
        }
        return $this;
    }

    /**
     * Set a single HTTP response header.
     */
    public function header(string $key, string $value): self
    {
        if (!headers_sent()) {
            header("{$key}: {$value}");
        }
        return $this;
    }

    /**
     * Set Content-Type header.
     */
    public function contentType(string $type = 'text/html', string $charset = 'utf-8'): self
    {
        return $this->header('Content-Type', "{$type}; charset={$charset}");
    }

    /* =========================================================================
     | [GÜVENLİK · http #11] Çerez bayrakları.
     |
     | NEDEN `bool $secure = false` KALDIRILDI: düz HTTP'de de `false` geldiği
     | için üretilen her çerez HTTPS'te `Secure` bayrağı taşımadan gidiyordu.
     | Artık `?bool $secure = null` "karar verme" demek: karar
     | `RequestEnvironment::isHttpsRequest()` (A0-4'ün TEK HTTPS kaynağı) verir.
     | Çağıran yine açıkça `true`/`false` verirse o karara saygı duyulur.
     |
     | GERİ UYUMLULUK: `$sameSite` EN SONA ve isteğe bağlı eklendi; konumsal
     | argümanla çağıran hiçbir mevcut kullanım bozulmaz (imza `ResponseInterface`
     | ile birebir aynı kalır). `SameSite=Lax` çapraz-site POST'un çerezi
     | göndermesini engeller — CSRF için ikinci katman.
     * ========================================================================= */
    public function cookie(string $name, string $value, int $minutes = 60, string $path = '/', ?string $domain = null, ?bool $secure = null, bool $httpOnly = true, ?string $sameSite = 'Lax'): self
    {
        $isSecure = $secure ?? RequestEnvironment::isHttpsRequest();

        setcookie($name, $value, [
            'expires'  => time() + ($minutes * 60),
            'path'     => $path,
            'domain'   => $domain ?? '',
            'secure'   => $isSecure,
            'httponly' => $httpOnly,
            'samesite' => $sameSite,
        ]);

        return $this;
    }

    /**
     * Send no-content response (204).
     */
    public function noContent(): void
    {
        $this->status(204);
        exit;
    }
}
