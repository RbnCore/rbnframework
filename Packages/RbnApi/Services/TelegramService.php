<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnApi\Services;

use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * TelegramService - RBN Framework Telegram Bot Gateway 📨🛰️⚓
 *
 * RBN Framework: Projelerin kullanabileceği TEK GENEL Telegram altyapısı.
 *
 * KAPSAM (Telegram'a özgü ve proje bağımsız olan her şey):
 *  - Bot API çağrıları: sendMessage, setWebhook, getWebhookInfo, deleteWebhook, getMe
 *  - Ortak bot kimliği: token / kullanıcı adı / webhook secret'ı `z_settings_api`den
 *  - Webhook `secret_token` üretimi ve `hash_equals` ile doğrulaması
 *  - Deep-link `/start KOD` üretme, doğrulama, metinden ayıklama ve link kurma
 *
 * KAPSAM DIŞI (her projenin kendi iş kuralıdır, proje servisinde kalır):
 *  - EVENT_RULES, cooldown sayaçları, `telegram_state` kalıcı durumu,
 *    panel ayarları (chat_id + bildirim bayrakları), agent payload dağıtımı.
 *
 * KULLANIM:
 *  $telegram = $this->service('telegram');
 *  $telegram->sendMessage($chatId, $text);
 *  $code  = $telegram->generateStartCode();
 *  $link  = $telegram->buildStartLink($code);
 *  $ok    = $telegram->verifyWebhookSecret($headerValue);
 *
 * @property \Rbn\Framework\Packages\RbnApi\Providers\TelegramProvider $apiTelegram
 */
class TelegramService extends BaseService
{
    // -----------------------------------------------------------------
    // ORTAK BOT KIMLIGI
    // -----------------------------------------------------------------

    /**
     * Ortak bot token'ini okur. BOS DONEBILIR (fail-closed).
     * Değer bu servis sınırından çıkmaz.
     */
    public function botToken(): string
    {
        return $this->provider('apiTelegram')->botToken();
    }

    /** Ortak bot kullanıcı adı (t.me bağlama linki bununla kurulur) */
    public function botUsername(): string
    {
        return $this->provider('apiTelegram')->botUsername();
    }

    /** Telegram API kök noktası (test için `z_settings_api`den ezilebilir) */
    public function apiBase(): string
    {
        return $this->provider('apiTelegram')->apiBase();
    }

    /** Bot yapılandırması hazır mı? (token + kullanıcı adı) */
    public function isBotConfigured(): bool
    {
        return $this->provider('apiTelegram')->isBotConfigured();
    }

    // -----------------------------------------------------------------
    // DOGRULAMA
    // -----------------------------------------------------------------

    /** Chat ID biçimini doğrular */
    public function isValidChatId(string $chatId): bool
    {
        return $this->provider('apiTelegram')->isValidChatId($chatId);
    }

    /** Deep-link başlangıç kodunun biçimini doğrular (32 hex) */
    public function isValidStartCode(string $code): bool
    {
        return $this->provider('apiTelegram')->isValidStartCode($code);
    }

    // -----------------------------------------------------------------
    // DEEP-LINK /START KODU
    // -----------------------------------------------------------------

    /** Tek kullanımlık, ~15 dakika geçerli rastgele bağlama kodu üretir */
    public function generateStartCode(): string
    {
        return $this->provider('apiTelegram')->generateStartCode();
    }

    /** Güncelleme metnindeki `/start KOD` değerini ayıklar (yoksa boş string) */
    public function parseStartCode(string $text): string
    {
        return $this->provider('apiTelegram')->parseStartCode($text);
    }

    /** `https://t.me/<bot>?start=<kod>` bağlama linki (bot yoksa null) */
    public function buildStartLink(string $code): ?string
    {
        return $this->provider('apiTelegram')->buildStartLink($code);
    }

    // -----------------------------------------------------------------
    // WEBHOOK GIZLI ANAHTARI
    // -----------------------------------------------------------------

    /**
     * Webhook `secret_token` değerini okur; yoksa üretip `z_settings_api`ye yazar.
     * Değer hiçbir yere (log/view/hata) yazılmaz.
     */
    public function webhookSecret(bool $persist = true): string
    {
        return $this->provider('apiTelegram')->webhookSecret($persist);
    }

    /** Gelen webhook secret header'ını `hash_equals` ile doğrular (fail-closed) */
    public function verifyWebhookSecret(string $given): bool
    {
        return $this->provider('apiTelegram')->verifyWebhookSecret($given);
    }

    // -----------------------------------------------------------------
    // BOT API CAGRILARI
    // -----------------------------------------------------------------

    /** Bir sohbete mesaj gönderir (HTML, önizleme kapalı) */
    public function sendMessage(string $chatId, string $text, array $options = []): array
    {
        return $this->provider('apiTelegram')->sendMessage($chatId, $text, $options);
    }

    /** Bot'u bir URL'ye webhook'a bağlar (gizli secret_token ile) */
    public function setWebhook(string $url, array $options = []): array
    {
        return $this->provider('apiTelegram')->setWebhook($url, $options);
    }

    /** Mevcut webhook kaydını okur (getWebhookInfo) */
    public function getWebhookInfo(): array
    {
        return $this->provider('apiTelegram')->getWebhookInfo();
    }

    /** Kayıtlı webhook'u kaldırır */
    public function deleteWebhook(): array
    {
        return $this->provider('apiTelegram')->deleteWebhook();
    }

    /** Bot kimliğini okur (getMe) */
    public function getMe(): array
    {
        return $this->provider('apiTelegram')->getMe();
    }

    /** Düşük seviye Bot API çağrısı (tüm metotlar için) */
    public function call(string $method, array $payload = [], array $options = []): array
    {
        return $this->provider('apiTelegram')->call($method, $payload, $options);
    }

    // -----------------------------------------------------------------
    // METIN YARDIMCILARI
    // -----------------------------------------------------------------

    /** Metni Telegram limitlerine göre kısaltır */
    public function sanitizeText(string $text): string
    {
        return $this->provider('apiTelegram')->sanitizeText($text);
    }

    /** Güvenilmeyen metni HTML kaçışı ile sarar */
    public function escape(string $value): string
    {
        return $this->provider('apiTelegram')->escape($value);
    }

    /** Telegram hata açıklamasından olası token sızıntısını temizler */
    public function cleanDescription(string $description): string
    {
        return $this->provider('apiTelegram')->cleanDescription($description);
    }
}
