<?php
namespace Rbn\Framework\Core\System\Kernel;

use Rbn\Framework\Core\System\Kernel\Base\PreBoot;
use Rbn\Framework\Core\System\Kernel\Base\KernelFactory;
use Rbn\Framework\Core\Routes\Route;
use Rbn\Framework\Core\Services\Gatekeepers\BootSentinel;

/**
 * Bootstrap - The thin orchestrator for RBN Framework 3.5. 🤵‍♂️✨🛡️
 */
class Bootstrap
{
    private static array $appContext = [];

    /**
     * Entry point for Web requests.
     */
    public static function run(string $publicPath, array $config = []): void
    {
        // [FW-CANLI-ONCESI-2] PHP surum kapisi (>= 8.3) — framework'un EN ERKEN
        // calisan kodu. `vendor/autoload.php` bu dosyayi zaten yuklemis olsa da
        // surum yanlissa (canlida belirlenemiyor) hata mesaji "Parse error"
        // degil, bakim mesaji olsun. Gercek koruma `domains/*/index.php` ve
        // `rbn` icinde autoload ONCESI yapilir; burasi savunma derinligidir.
        require_once __DIR__ . '/Base/PhpVersionGate.php';
        Base\PhpVersionGate::enforce();

        try {
            // 🛑 RBN 3.5: K-02 — Hata ayrıntısı görünürlüğü ORTAMA BAĞLI.
            // İlk çağrı ortam tespitinden ÖNCE gelir: RBN_DEV henüz yok, guvenli
            // varsayilan KAPALI. İkinci çağrı PreBoot'dan SONRA gelir.
            self::configureErrorVisibility();
            self::sendBaseSecurityHeaders();

            // 1. Pre-flight Orchestration (Paths, Autoload, Sentinel) 🚀🛰️
            PreBoot::orchestrate($publicPath, $config);

            // Ortam tespiti tamam: geliştirme ise ayrıntılar ekrana da dönsün. 🔍
            self::configureErrorVisibility();

            // 2. Kernel Assembly & Boot 🏛️⚖️🛡️⚓
            ob_start();
            $kernel = KernelFactory::create($publicPath, $config);
            $kernel->boot();

            // 3. Final Dispatch 🛣️
            Route::run();

            // 🏁 RBN 3.5: Masterpiece Graceful Termination
            $kernel->terminate();

        } catch (\Throwable $e) {
            // [RBN BOOTGUARD] Final terminal catch 💀
            if (class_exists(BootSentinel::class)) {
                (new BootSentinel())->dispatch($e);
            }
            self::renderTerminalFailure($e);
        }
    }

    /**
     * K-03: Boot'un en dar çıkış yolu — istisna ayrıntısını ziyaretçiye basmaz.
     *
     * Geliştirme ortamında mevcut ayrıntı (KAÇIŞLI) basılır; üretimde yalnız
     * genel mesaj + hata kimliği gösterilir, ayrıntı yalnız `error_log()`a gider.
     */
    public static function renderTerminalFailure(\Throwable $e): void
    {
        $errorId = strtoupper(bin2hex(random_bytes(4)));

        error_log(sprintf(
            '[RBN-BOOTGUARD] id=%s type=%s message=%s file=%s line=%d',
            $errorId,
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        ));

        if (defined('RBN_DEV') && RBN_DEV === true) {
            die('RBN Masterpiece Terminal Failure: '
                . htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        }

        die('Sistem şu an kullanılamıyor. Hata kimliği: ' . $errorId);
    }

    /**
     * K-02: Hata ayrıntılarının ekrana dökülmesini ortama bağlar.
     *
     * "Dev mi?" kararının TEK merkezi `PreBoot::detectEnvironment()` (RBN_DEV).
     * UYDURMA bir dev tanımı yoktur; burada yalnız o sabit okunur.
     * `RBN_DEV` tanımlı DEĞİLSE (erken çağrı) sonuç KAPALI'dır (fail-closed):
     * üretimde hiçbir ayrıntı sızmaz, ayrıntı yalnız `error_log` kanalına gider.
     */
    public static function configureErrorVisibility(): void
    {
        $isDev = defined('RBN_DEV') && RBN_DEV === true;

        @ini_set('display_errors', $isDev ? '1' : '0');
        @ini_set('log_errors', '1');
        @error_reporting(E_ALL); // log tam olmalı: ekranda değil, dosyada E_ALL
    }

    /**
     * ORTA-2 / ORTA-1 (kod tarafi): PHP surum bilgisini (`X-Powered-By`) kaldirir ve
     * dusuk riskli temel basliklari YOKSA ekler. Projenin kendi koydugu baslik EZILMEZ.
     * HSTS / CSP / X-Frame-Options / Permissions-Policy projeye ozgu oldugundan sunucu
     * (nginx/Apache) ya da proje katmaninda tanimlanir; burada KASITLI olarak yoktur.
     */
    public static function sendBaseSecurityHeaders(): void
    {
        if (PHP_SAPI === 'cli' || headers_sent()) {
            return;
        }

        header_remove('X-Powered-By');

        $present = [];
        foreach (headers_list() as $line) {
            $present[strtolower(trim(explode(':', $line, 2)[0]))] = true;
        }

        $defaults = [
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ];
        foreach ($defaults as $name => $value) {
            if (!isset($present[strtolower($name)])) {
                header($name . ': ' . $value, false);
            }
        }
    }

    /**
     * Context Accessors (Backward Compatibility)
     */
    public static function getAppContext(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return self::$appContext;
        }
        return self::$appContext[$key] ?? $default;
    }
    public static function setAppContext(string $key, mixed $value): void
    {
        self::$appContext[$key] = $value;
    }
}
