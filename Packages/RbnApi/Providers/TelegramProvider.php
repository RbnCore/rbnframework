<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnApi\Providers;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * TelegramProvider - Telegram Bot API Connectivity Hub 📨🛰️⚓
 *
 * RBN Framework: Sadece Telegram'a OZGUL, PROJEYDEN BAGIMSIZ (generic) cerceve.
 *
 * BU SINIFIN SORUMLULUGU:
 *  - Bot API cagrilari (sendMessage, setWebhook, getWebhookInfo, deleteWebhook, getMe)
 *  - Ortak bot kimligi: token, kullanici adi, webhook secret'i `z_settings_api`
 *    uzerinden SettingsApiRepository (getApiKey/saveApiKey) ile okumak/uretmek.
 *  - Webhook `secret_token` dogrulamasi (hash_equals, zamanlama guvenli).
 *  - Deep-link `/start KOD` akisinin GENERIC kismi: kod uretme, bicim dogrulama,
 *    guncelleme metninden kod ayiklama ve t.me baglama linki olusturma.
 *
 * BU SINIFIN YAPMADIGI (projeye ozgu is kurallari - PROJEDE KALIR):
 *  - Hangi olayin kimine gidecegi, cooldown sayaclari, `telegram_state` satiri,
 *    panel ayarlari (chat_id + bayraklar), EVENT_RULES, dispatchAgentPayload.
 *  Bunlar proje kuralidir; her proje kendi kuralini kendi servisinde yazar ve
 * sadece BURADAKI generic metotlari devreye alir.
 *
 * GUVENLIK (KATMANLI):
 *  - Token bu metodun disina cikmaz; yalnizca `call()` icinde URL'e girer.
 *  - Token hicbir loga, hata mesajina, view'a veya commit'e yazilmaz.
 *  - Token yoksa FAIL-CLOSED: sahte "gonderdim" donmez.
 */
class TelegramProvider extends BaseComponent
{
    // -----------------------------------------------------------------
    // z_settings_api anahtarlari (SSoT) - tum projelerde BIREBIR ayni adlar
    // -----------------------------------------------------------------

    /** Bot token (gizli; yalnizca z_settings_api icinde, hicbir yerde yazilmaz) */
    public const API_KEY_TOKEN = 'TELEGRAM_BOT_TOKEN';

    /** Bot kullanici adi (orn. proje_bot). Baglama linki bununla kurulur. */
    public const API_KEY_USERNAME = 'TELEGRAM_BOT_USERNAME';

    /** Telegram webhook gizli anahtari (setWebhook secret_token) */
    public const API_KEY_WEBHOOK_SECRET = 'TELEGRAM_WEBHOOK_SECRET';

    /** Test/saghte sunucu icin API kok noktasi ezmesi (opsiyonel) */
    public const API_KEY_API_BASE = 'TELEGRAM_API_BASE';

    /** Telegram Bot API Kok Noktasi (SSoT) */
    public const TELEGRAM_API_BASE = 'https://api.telegram.org';

    // -----------------------------------------------------------------
    // Bicim ve zamanlama sabitleri (SSoT)
    // -----------------------------------------------------------------

    /**
     * Chat ID Bicim Dogrulamasi. Ozel sohbetler pozitif, gruplar/kanallar negatif.
     */
    public const CHAT_ID_PATTERN = '/^-?\d{1,20}$/';

    /**
     * Baglama (deep-link start) kodu bicimi: 32 hex karakter = 128 bit entropi.
     */
    public const START_CODE_PATTERN = '/^[a-f0-9]{32}$/';

    /** Baglama kodu uretiminde kullanilan bayt sayisi (16 bayt = 32 hex) */
    public const START_CODE_BYTES = 16;

    /** Baglama kodu gecerlilik suresi (saniye) - ~15 dakika */
    public const START_CODE_TTL_SEC = 900;

    /** Gonderim Zaman Asimi (saniye) */
    public const SEND_TIMEOUT_SEC = 10;

    /** Baglanti Zaman Asimi (saniye) */
    public const CONNECT_TIMEOUT_SEC = 5;

    /** Telegram HIZ SINIRI: chat basina ~1 mesaj/sn (sikici projeler kullanir) */
    public const MIN_CHAT_INTERVAL_MS = 1100;

    /** Telegram metin ust siniri (4096); guvenli kutup 4000 */
    public const TEXT_SOFT_LIMIT = 4000;

    /** Telegram webhook secret_token izinli karakterler (1-256, A-Z a-z 0-9 _ -) */
    public const WEBHOOK_SECRET_PATTERN = '/^[A-Za-z0-9_-]{32,256}$/';

    /**
     * API base ezmesi icin izinli bicim (SSRF korumasi).
     * Ay deposu ele gecse bile keyfi bir ic ag ucuna yonlendirme yapilamaz.
     */
    public const API_BASE_PATTERN = '#^https?://[A-Za-z0-9._\-]+(:\d{1,5})?$#';

    /**
     * Deep-link /start metninden kod ayiklama deseni.
     * Telegram metni "/start KOD" seklindedir; grup yonlendirmesinde
     * "@botadi" soneki gelir. Bastaki slash opsiyoneldir.
     */
    public const START_TEXT_PATTERN = '/^\/?start(?:@\S+)?\s+([a-f0-9]{32})$/i';

    /** @var array<string,string> istek omrune kalan ortak bot degerleri */
    private array $botCache = [];

    // -----------------------------------------------------------------
    // 1) ORTAK BOT KIMLIGI (z_settings_api)
    // -----------------------------------------------------------------

    /**
     * Ortak bot token'ini z_settings_api'den okur. BOS DONEBILIR (fail-closed).
     *
     * Token bu metodun DISINA cikmaz; yalnizca `call()` icinde URL'e girer.
     * Hicbir log/hata mesaji/view alanina yazilmaz.
     */
    public function botToken(): string
    {
        if (array_key_exists('token', $this->botCache)) {
            return $this->botCache['token'];
        }

        $repo = $this->repository('project.settingsApi');
        $token = '';
        if ($repo) {
            $token = trim((string) ($repo->getApiKey(self::API_KEY_TOKEN) ?? ''));
        }

        // Fail-closed: token yoksa/bozksa gonderim yapilmayacak.
        $this->botCache['token'] = $token;

        return $token;
    }

    /**
     * Ortak bot kullanici adi. Baglama linki bu degerle kurulur.
     * BotFather kullanicilari her zaman [A-Za-z][A-Za-z0-9_]{3,39} ile biter.
     */
    public function botUsername(): string
    {
        if (array_key_exists('username', $this->botCache)) {
            return $this->botCache['username'];
        }

        $repo = $this->repository('project.settingsApi');
        $username = '';
        if ($repo) {
            $raw = trim((string) ($repo->getApiKey(self::API_KEY_USERNAME) ?? ''));
            $username = (bool) preg_match('/^[A-Za-z][A-Za-z0-9_]{3,39}$/', $raw) ? $raw : '';
        }

        $this->botCache['username'] = $username;

        return $username;
    }

    /**
     * Telegram API kok noktasi. Test icin z_settings_api uzerinden ezilebilir;
     * gecerli bir http(s) bicimi degilse SSoT sabiti kullanilir (SSRF korumasi).
     */
    public function apiBase(): string
    {
        $repo = $this->repository('project.settingsApi');
        $raw = $repo ? trim((string) ($repo->getApiKey(self::API_KEY_API_BASE) ?? '')) : '';

        if ($raw !== '' && (bool) preg_match(self::API_BASE_PATTERN, $raw)) {
            return rtrim($raw, '/');
        }

        return self::TELEGRAM_API_BASE;
    }

    /**
     * Bot yapilandirmasi hazir mi? (token + username). Panel uyarisi icin.
     */
    public function isBotConfigured(): bool
    {
        return $this->botToken() !== '' && $this->botUsername() !== '';
    }

    // -----------------------------------------------------------------
    // 2) DOGRULAMA
    // -----------------------------------------------------------------

    /** Chat ID bicimini dogrular (ozel sohbet/grup/kanal) */
    public function isValidChatId(string $chatId): bool
    {
        return (bool) preg_match(self::CHAT_ID_PATTERN, trim($chatId));
    }

    /** Deep-link /start kodunun bicimini dogrular (32 hex) */
    public function isValidStartCode(string $code): bool
    {
        return (bool) preg_match(self::START_CODE_PATTERN, strtolower(trim($code)));
    }

    // -----------------------------------------------------------------
    // 3) DEEP-LINK /START KODU (uretme - dogrulama - ayiklama - link)
    // -----------------------------------------------------------------

    /**
     * Tek kullanimlik, ~15 dakika gecerli rastgele baglama kodu uretir.
     * 128 bit entropi; kisa veya tahmin edilebilir kod uretilmez.
     */
    public function generateStartCode(): string
    {
        return bin2hex(random_bytes(self::START_CODE_BYTES));
    }

    /**
     * Guncelleme metnindeki `/start KOD` degerini ayiklar.
     * Kod yoksa (veya bicim gecersizse) BOS STRING doner.
     */
    public function parseStartCode(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        if ((bool) preg_match(self::START_TEXT_PATTERN, $text, $m)) {
            return strtolower($m[1]);
        }

        return '';
    }

    /**
     * `https://t.me/<bot>?start=<kod>` baglama linkini uretir.
     * Bot kullanici adi tanimli degilse NULL doner (cagricı bunu hata olarak ele alir).
     */
    public function buildStartLink(string $code): ?string
    {
        $code = strtolower(trim($code));
        $username = $this->botUsername();

        if ($username === '' || !$this->isValidStartCode($code)) {
            return null;
        }

        return 'https://t.me/' . $username . '?start=' . $code;
    }

    // -----------------------------------------------------------------
    // 4) WEBHOOK GIZLI ANAHTARI (setWebhook secret_token)
    // -----------------------------------------------------------------

    /**
     * Telegram webhook gizli anahtari (secret_token).
     *
     * Telegram'in setWebhook cagrisinda verilen degerdir ve her guncellemede
     * `X-Telegram-Bot-Api-Secret-Token` header'i ile gelir. `z_settings_api`
     * icinde `TELEGRAM_WEBHOOK_SECRET` anahtari ile saklanir.
     *
     * Deger ilk ihtiyacta 64 karakterlik `random_bytes` ile URETILIR ve DB'ye
     * yazilir; hicbir dosyaya, koda veya loga yazilmaz.
     *
     * @param bool $persist Yoksa uretme (salt okunur arama)
     */
    public function webhookSecret(bool $persist = true): string
    {
        if (array_key_exists('webhook_secret', $this->botCache)) {
            return $this->botCache['webhook_secret'];
        }

        $repo = $this->repository('project.settingsApi');
        if (!$repo) {
            return '';
        }

        $raw = trim((string) ($repo->getApiKey(self::API_KEY_WEBHOOK_SECRET) ?? ''));
        // Telegram secret_token: 1-256 karakter, A-Z a-z 0-9 _ -
        if ((bool) preg_match(self::WEBHOOK_SECRET_PATTERN, $raw)) {
            $this->botCache['webhook_secret'] = $raw;
            return $raw;
        }

        if (!$persist) {
            return '';
        }

        $secret = bin2hex(random_bytes(32));
        if (!$repo->saveApiKey(self::API_KEY_WEBHOOK_SECRET, $secret, 'telegram', 'Telegram Webhook Secret')) {
            return '';
        }

        $this->botCache['webhook_secret'] = $secret;
        return $secret;
    }

    /**
     * Gelen webhook gizli anahtarini zamanlama guvenli sekilde dogrular.
     *
     * FAIL-CLOSED: secret tanimli degilse veya gelen deger bossa FALSE doner.
     * Boylece internete acik bir route'a secret'siz guncelleme kabul edilmez.
     */
    public function verifyWebhookSecret(string $given): bool
    {
        $expected = $this->webhookSecret();
        $given = trim($given);

        if ($expected === '' || $given === '') {
            return false;
        }

        return hash_equals($expected, $given);
    }

    // -----------------------------------------------------------------
    // 5) BOT API CAGRILARI
    // -----------------------------------------------------------------

    /**
     * Telegram Bot API'ye dusuk seviye cagri yapar.
     *
     * FAIL-CLOSED: token tanimli degilse hicbir ag trafigi olusmaz.
     *
     * @param string               $method  Bot API metot adi (orn. sendMessage)
     * @param array<string, mixed> $payload Metot govdesi
     * @param array<string, mixed> $options timeout / connect_timeout / retries
     * @return array{ok:bool, http_code:int, error_code:int, description:string, result:mixed, data:mixed, status:string}
     */
    public function call(string $method, array $payload = [], array $options = []): array
    {
        $token = $this->botToken();
        if ($token === '') {
            return $this->emptyResult(
                0,
                'Telegram bot token tanimli degil. z_settings_api -> ' . self::API_KEY_TOKEN . ' ayarini kontrol edin.'
            );
        }

        $method = ltrim(trim($method), '/');
        if ($method === '') {
            return $this->emptyResult(0, 'Gecersiz Telegram Bot API metot adi.');
        }

        $url = $this->apiBase() . '/bot' . $token . '/' . $method;

        $response = $this->remote->post(
            $url,
            $payload,
            [],
            true,
            array_merge([
                'timeout'         => self::SEND_TIMEOUT_SEC,
                'connect_timeout' => self::CONNECT_TIMEOUT_SEC,
                'retries'         => 2,
            ], $options)
        );

        $httpCode = (int) ($response['http_code'] ?? 0);
        $data = $response['data'] ?? null;

        if (!is_array($data)) {
            return $this->emptyResult(
                $httpCode,
                "Telegram API'sine ulasilamadi (HTTP {$httpCode}). Baglanti veya DNS sorunu olabilir."
            );
        }

        $ok = !empty($data['ok']);

        return [
            'ok'          => $ok,
            'http_code'   => $httpCode,
            'error_code'  => (int) ($data['error_code'] ?? 0),
            'description' => $this->cleanDescription((string) ($data['description'] ?? '')),
            'result'      => $data['result'] ?? null,
            'data'        => $data,
            'status'      => (string) ($response['status'] ?? ''),
        ];
    }

    /**
     * Bir sohbete metin gonderir (HTML parse_mode, onizleme kapali).
     *
     * Metin sorumlulugu burada degil; sadece Telegram limitlerine gore kirpilir.
     *
     * @return array{ok:bool, http_code:int, error_code:int, description:string, result:mixed, data:mixed, status:string}
     */
    public function sendMessage(string $chatId, string $text, array $options = []): array
    {
        $chatId = trim($chatId);
        if (!$this->isValidChatId($chatId)) {
            return $this->emptyResult(0, 'Gecersiz chat id.');
        }

        return $this->call('sendMessage', [
            'chat_id'                 => $chatId,
            'text'                    => $this->sanitizeText($text),
            'parse_mode'              => 'HTML',
            'disable_web_page_preview' => true,
        ], $options);
    }

    /**
     * Bot'u bir URL'ye webhook'a baglar.
     *
     * Guvenli varsayilan: gizli `secret_token` verilmezse provider otomatik
     * uretilir (ve `z_settings_api` icine yazilir) ve Telegram'a bildirilir.
     * Boylece internete acik bir route'a sahte guncelleme atilamaz.
     *
     * @param array<string, mixed> $options Ek Bot API parametreleri (allowed_updates vb.)
     */
    public function setWebhook(string $url, array $options = []): array
    {
        $url = trim($url);
        if ($url === '' || !str_starts_with($url, 'https://')) {
            return $this->emptyResult(0, 'Webhook URL bos veya https:// degil.');
        }

        $secret = (string) ($options['secret_token'] ?? $this->webhookSecret());
        if ($secret === '') {
            return $this->emptyResult(0, 'Webhook secret_token uretilemedi.');
        }

        unset($options['secret_token']);

        return $this->call('setWebhook', array_merge([
            'url'         => $url,
            'secret_token' => $secret,
        ], $options));
    }

    /**
     * Mevcut webhook kaydini okur (diagnostik: getWebhookInfo).
     *
     * @return array{ok:bool, http_code:int, error_code:int, description:string, result:mixed, data:mixed, status:string}
     */
    public function getWebhookInfo(): array
    {
        return $this->call('getWebhookInfo');
    }

    /**
     * Kayitli webhook'u kaldirir (polling'e gecis veya temizlik icin).
     */
    public function deleteWebhook(): array
    {
        return $this->call('deleteWebhook', ['drop_pending_updates' => false]);
    }

    /**
     * Bot kimligini okur (getMe) - baglanti saglamasi icin pratik kontrol.
     */
    public function getMe(): array
    {
        return $this->call('getMe');
    }

    // -----------------------------------------------------------------
    // 6) METIN YARDIMCILARI
    // -----------------------------------------------------------------

    /**
     * Metni Telegram limitlerine gore kisaltir.
     * Mesaj govdesi bu metodun DISINDA gonderilmez; her cagri buradan gecer.
     */
    public function sanitizeText(string $text): string
    {
        if (mb_strlen($text) > self::TEXT_SOFT_LIMIT) {
            $text = mb_substr($text, 0, self::TEXT_SOFT_LIMIT) . "\u{2026}";
        }

        return $text;
    }

    /**
     * Guvenilmeyen metni HTML kacis ile sarar (parse_mode=HTML kullanilirken).
     */
    public function escape(string $value): string
    {
        return htmlspecialchars(trim($value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Telegram hata aciklamasindan olasi token sizintisini temizler.
     * (Telegram genelde token'i yankilamaz; katmanli savunma.)
     */
    public function cleanDescription(string $description): string
    {
        $description = trim($description);
        if ($description === '') {
            return 'Bilinmeyen Telegram hatasi.';
        }

        return (string) preg_replace_callback(
            '/\d{5,20}:[A-Za-z0-9_-]{30,}/',
            static fn (array $m): string => substr($m[0], 0, strpos($m[0], ':') + 1) . str_repeat('*', 20),
            $description
        );
    }

    /**
     * Ortak (token'siz) sonuc bicimi uretir - cagirmaya gore tutarli kontrat.
     *
     * @return array{ok:bool, http_code:int, error_code:int, description:string, result:mixed, data:mixed, status:string}
     */
    private function emptyResult(int $httpCode, string $description): array
    {
        return [
            'ok'          => false,
            'http_code'   => $httpCode,
            'error_code'  => 0,
            'description' => $description,
            'result'      => null,
            'data'        => null,
            'status'      => 'error',
        ];
    }
}
