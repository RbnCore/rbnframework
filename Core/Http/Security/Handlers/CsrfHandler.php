<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Http\Security\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * CsrfHandler - The Anti-Forgery Actor 🛡️🔑
 * 
 * RBN 3.5: Atomic actor for CSRF token lifecycle management.
 */
class CsrfHandler extends BaseComponent
{
    public function generate(): array
    {
        $sessions = $this->storage->sessions();
        $storedToken = $sessions->get('csrf_token');
        $storedTime = $sessions->get('csrf_token_time');

        // Eğer mevcut session'da geçerli (1 saatten eski olmayan) bir token varsa, onu koru ve yeniden kullan 🛡️✨
        if ($storedToken && $storedTime && (time() - (int)$storedTime < 3600)) {
            return [
                'token' => $storedToken,
                'time' => (int)$storedTime,
                'expires_in' => 3600 - (time() - (int)$storedTime)
            ];
        }

        $tokenData = [
            'token' => bin2hex(random_bytes(32)),
            'time' => time(),
            'expires_in' => 3600
        ];

        $sessions->set('csrf_token', $tokenData['token']);
        $sessions->set('csrf_token_time', $tokenData['time']);

        return $tokenData;
    }

    /**
     * Validate a given token against the session.
     */
    public function validate(string $token): bool
    {
        $sessions = $this->storage->sessions();
        $storedToken = $sessions->get('csrf_token');
        $storedTime = $sessions->get('csrf_token_time');

        if (!$storedToken || !$storedTime) {
            return false;
        }

        if (time() - (int) $storedTime > 3600) {
            $sessions->delete('csrf_token');
            $sessions->delete('csrf_token_time');
            return false;
        }

        return hash_equals((string) $storedToken, $token);
    }

    /**
     * High-level verification against the session token.
     *
     * [FW-F08 · 2026-10-03 · zeki-6eb7f5] ONCEDEN BURADA bir "DEVELOPER BYPASS"
     * blogu vardi: `user_role === 'developer'` veya `is_master_developer === true`
     * ise `verify()` token'a BAKMADAN `success = true` donuyordu; yani bu iki
     * oturum tipi tarayici tabanli CSRF saldirisina tamamen acikti (F-08).
     *
     * Artik OLAY YOK: dogrulama TUM roller icin ayni ve zorunludur. Token
     * uretimi (`generate()`), yasam suresi (1 saat) ve diger rollerin davranisi
     * AYNEN korunur; fail-closed esastir (token yoksa/yanlissa/suresi dolmussa
     * `success = false`). Yeni bir muafiyet veya kisa yol EKLENMEMISTIR.
     */
    public function verify(array $data): array
    {
        $sessions = $this->storage->sessions();

        if (!isset($data['csrf_token'])) {
            return [
                'success' => false,
                'data' => $data,
                'new_token' => $this->generate(),
                'message' => 'Token missing 🧱'
            ];
        }

        if (!$this->validate($data['csrf_token'])) {
            return [
                'success' => false,
                'data' => $data,
                'new_token' => $this->generate(),
                'message' => 'Invalid or expired CSRF token 🛡️'
            ];
        }

        return [
            'success' => true,
            'data' => $data,
            'new_token' => [
                'token' => $sessions->get('csrf_token') ?? $data['csrf_token'],
                'time' => $sessions->get('csrf_token_time') ?? time()
            ]
        ];
    }
}
