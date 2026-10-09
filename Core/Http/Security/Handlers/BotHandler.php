<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Http\Security\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * BotHandler - The Bot-Trap Actor 🛡️🏮
 * 
 * RBN Framework: Atomic actor for Honeypot validation and User-Agent screening.
 */
class BotHandler extends BaseComponent
{
    /**
     * Get or generate a randomized honeypot field name from session. 🛰️
     */
    public function getDynamicName(): string
    {
        $session = $this->session();
        $hpField = $session->get('_rbn_hp_field');

        if (empty($hpField)) {
            $hpField = '_hp_' . bin2hex(random_bytes(4));
            $session->set('_rbn_hp_field', $hpField);
        }

        return $hpField;
    }

    /**
     * Honeypot (Honeybot) validation.
     * Hidden field must be empty.
     */
    public function validateHoneypot(array $data, string $field = 'website'): bool
    {
        $value = $data[$field] ?? null;

        // `empty()` "0" değerini boş sayıp botu geçiriyordu (F-12/F-13).
        if (is_array($value)) {
            return $value === [];
        }

        return trim((string) $value) === '';
    }

    /**
     * User-Agent (Header) control.
     * Detects suspicious scripted bots.
     */
    public function validateUserAgent(): bool
    {
        $ua = $this->request->userAgent();

        if (empty($ua)) {
            return false;
        }

        // [F-11] Liste tek kaynaktan (`SUSPICIOUS_USER_AGENTS`) gelir ve denetim
        // saf yardımcıya devredilmiştir (birim testlenebilir).
        return !self::isSuspiciousUserAgent($ua);
    }

    /**
     * [F-11] Şüpheli User-Agent dizgeleri (ilk 6 kelime aynen korundu).
     *
     * ÖLÇÜLEN BOŞLUK: güvenlik/araştırma araçları (`sqlmap`, `nikto`, `nuclei`,
     * `masscan`) kullanıcı adımına kadar geçiyordu.
     *
     * KAPSAM BİLİNCİ SEÇİM: genel HTTP/SDK imzaları (`Java`, `axios`, `okhttp`,
     * `node-fetch`, `python-urllib` vb.) **eklenmedi** — bunlar meşru API/servis
     * trafiğinde de görünür ve IP katmanı kararı (Zeki, `enforce`) olmadan
     * reddetmek hizmet kesintisi riski taşır. Buradaki liste yalnız **açıkça
     * saldırı/veri-tarama aracı** imzalarını kapsar.
     */
    public const SUSPICIOUS_USER_AGENTS = [
        'curl', 'python', 'go-http-client', 'postman', 'insomnia', 'wget',
        'sqlmap', 'nikto', 'nuclei', 'masscan',
    ];

    /**
     * Saf (yan etkisiz) User-Agent denetimi — birim testlenebilir olması için
     * `validateUserAgent()`'dan ayrıldı.
     */
    public static function isSuspiciousUserAgent(string $ua): bool
    {
        foreach (self::SUSPICIOUS_USER_AGENTS as $bot) {
            if (stripos($ua, $bot) !== false) {
                return true;
            }
        }

        return false;
    }
}
