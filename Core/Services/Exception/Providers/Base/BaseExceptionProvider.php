<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Exception\Providers\Base;

use Rbn\Framework\Core\Support\Definitions\System\FrameworkIdentity;
use Rbn\Framework\Core\Base\Services\BaseProvider;
use Rbn\Framework\Core\Services\Exception\Data\ShieldMetadata;

/**
 * BaseExceptionProvider - Abstract Foundation for Exception Organs 🛰️🛡️
 * 
 * RBN 3.5: Masterpiece Standard.
 * Extends 'BaseProvider' to inherit Discovery and Contextual services.
 * All specialized exception providers (except Survival) must extend this class.
 */
abstract class BaseExceptionProvider extends BaseProvider
{
    /**
     * FW-KARAR-2 / Z-1: Uretimde kullaniciya gosterilen genel ipucu metni.
     * `ExceptionHandler::publicHint()` ve `PreflightProvider::renderFatal()`
     * ile ayni kalibi paylasir.
     */
    public const GENERIC_HINT = 'Ayrıntılar sistem günlüğüne yazıldı. Sorun sürerse sistem yöneticisine başvurun.';

    /**
     * Ayrintili (ham) cikti yalniz yerel gelistirmede mi? 🛡️
     *
     * `RBN_DEV` tanimsizsa (cok erken boot) KAPALI sayilir — fail-closed.
     * Bu, `DUSUK-3` surum gizleme ve `K-06` ipucu gizleme kapilariyla ayni
     * karar kaynagini kullanir.
     */
    protected static function richOutputAllowed(): bool
    {
        return defined('RBN_DEV') && RBN_DEV === true;
    }

    /**
     * Kisa, tahmin edilemez hata kimligi (8 hex). Kullaniciya bu gosterilir;
     * ayrinti ayni kimlikle log'a yazilir.
     */
    protected static function newErrorId(): string
    {
        try {
            return strtoupper(bin2hex(random_bytes(4)));
        } catch (\Throwable) {
            return strtoupper(substr(md5(uniqid('', true)), 0, 8));
        }
    }

    /**
     * FW-KARAR-2 / Z-1: Uretimde ham istisna ayrintisini gizler. 🛡️🔇
     *
     * `RBN_DEV !== true` iken kullaniciya giden veriden `message` (ham
     * `getMessage()`), `class` (istisna sinif adi), `file`, `line`, `trace`
     * ve `snippet` AYRINTILARI SILINIR; yerine genel metin + hata kimligi
     * konur. Tam ayrinti **mevcut hata kanallarina** (`error_log` +
     * `LogHandler::failsafeLog`) ayni kimlikle yazilir — yeni kanal acilmaz.
     *
     * Gelistirmede (`RBN_DEV === true`) veri AYNEN dondurulur.
     *
     * @param string $genericMessage Kullaniciya gosterilecek genel metin.
     * @param bool   $hideType       `type` alani da gizlensin mi (sinif adi
     *                               tasiyan Survival yolu icin `true`).
     */
    protected static function redactForPublicOutput(
        array $data,
        string $genericMessage,
        string $genericHint = self::GENERIC_HINT,
        bool $hideType = false
    ): array {
        if (self::richOutputAllowed()) {
            return $data;
        }

        $errorId = self::newErrorId();
        $class = (string) ($data['class'] ?? '');
        $type = (string) ($data['type'] ?? $class);

        // Tam ayrinti: yalniz mevcut hata kanallari.
        @error_log(
            '[Shield][' . $errorId . '] ' . ($type !== '' ? $type : 'Hata') . ': '
            . (string) ($data['message'] ?? '') . ' | ' . (string) ($data['file'] ?? '') . ':'
            . (string) ($data['line'] ?? '') . ' | hint: ' . (string) ($data['hint'] ?? '')
        );

        \Rbn\Framework\Core\Services\Exception\Handlers\LogHandler::failsafeLog([
            'type' => ($type !== '' ? $type : 'Unknown'),
            'message' => (string) ($data['message'] ?? ''),
            'file' => (string) ($data['file'] ?? ''),
            'line' => (int) ($data['line'] ?? 0),
            'error_id' => $errorId,
        ], 'Public output redacted: ' . $errorId);

        $data['message'] = rtrim($genericMessage) . ' Hata kimliği: ' . $errorId;
        $data['hint'] = $genericHint;
        $data['error_id'] = $errorId;

        if ($hideType) {
            $data['type'] = 'Sistem Hatası';
        }

        unset(
            $data['class'],
            $data['file'],
            $data['line'],
            $data['trace'],
            $data['snippet'],
            $data['exception'],
            $data['context'],
            $data['is_database']
        );

        return $data;
    }

    /**
     * Get branding synchronization metadata (SSoT) 🏺⚓
     */
    protected function getBranding(): array
    {
        return [
            'name' => FrameworkIdentity::SHIELD_NAME,
            'slogan' => FrameworkIdentity::SHIELD_SLOGAN,
            'version' => FrameworkIdentity::SHIELD_VERSION,
            'icon' => ShieldMetadata::ICON,
            'favicon' => ShieldMetadata::FAVICON
        ];
    }

    /**
     * Get synchronized time for diagnostic reports 🕰️
     */
    protected function getNow(string $format = 'Y-m-d H:i:s'): string
    {
        return \now($format);
    }

    /**
     * Intelligent AJAX Detection 🛰️🧠
     * Checks for X-Requested-With or JSON Accept headers.
     */
    protected function isAjax(): bool
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
            (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));
    }

    /**
     * Standardized RBN Shield JSON Diagnostic Report 📓🧪
     * Used for AJAX and API callers to provide high-fidelity diagnostic data via F12.
     */
    protected function renderJson(array $data, int $code = 500): void
    {
        if (!headers_sent()) {
            http_response_code($code);
            header('Content-Type: application/json; charset=UTF-8');
        }

        // 🎯 RBN 3.5: Masterpiece JSON Signature
        $report = [
            'status' => 'error',
            '_is_rbn_diagnostic' => true, // 🛰️ Identification Flag for Debug Bridge
            'shield' => FrameworkIdentity::SHIELD_NAME . ' ' . FrameworkIdentity::SHIELD_VERSION,
            'timestamp' => $this->getNow(),
            'data' => $data
        ];

        echo json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * FW-A0-K1-DEBUG-KAPISI-99 (K-4): `<head>` icine basilan site URL'i.
     *
     * Ham `$_SERVER['HTTP_HOST']` yerine yalnizca DOGRULANMIS (PreBoot::normalizeHost)
     * ve `htmlspecialchars` ile kacisli host kullanilir. Gecersiz/hos Host'ta
     * gercek host SIZDIRILMAZ; yalnizca iz (`https://<guvenli-hata-sayfasi>`) basilir.
     */
    protected static function safeSiteUrl(): string
    {
        if (!isset($_SERVER['HTTP_HOST']) || !isset($_SERVER['REQUEST_URI'])) {
            return 'cli';
        }

        $host = '';
        if (class_exists(\Rbn\Framework\Core\System\Kernel\Base\PreBoot::class)) {
            $host = \Rbn\Framework\Core\System\Kernel\Base\PreBoot::normalizeHost((string) $_SERVER['HTTP_HOST']);
        }

        if ($host === '') {
            return 'https://<guvenli-hata-sayfasi>';
        }

        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $uri = (string) $_SERVER['REQUEST_URI'];
        // Kontrol karakterleri ve tirnak/isaret kirilmasini onle
        $uri = preg_replace('/[\x00-\x1F\x7F"\'<>\\\\]/', '', $uri) ?? '';

        return htmlspecialchars(
            $scheme . '://' . $host . $uri,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }

    /**
     * Centralized Autonomous Renderer (Save Mode) 🏛️🛡️⚓
     * 
     * RBN 3.5: Masterpiece Standard.
     * Manages layout inclusion, asset injection, and view rendering without 
     * relying on the framework's internal Service Hub.
     */
    protected static function renderAutonomous(string $view, array $data, int $code = 500): void
    {
        // 🔬 RBN 3.5: Kill any existing buffers to ensure a clean diagnostic output
        while (ob_get_level() > 0)
            ob_end_clean();

        if (!headers_sent()) {
            http_response_code($code);
            header("Content-Type: text/html; charset=UTF-8");
        }

        // 🎯 RBN 3.5: Global Masterpiece Branding Orchestration (Direct Metadata) 🛡️🚀
        $branding = [
            // 🛡️ Shield Core Identity (Requested Constants)
            'shield_name' => FrameworkIdentity::SHIELD_NAME,
            'shield_slogan' => FrameworkIdentity::SHIELD_SLOGAN,
            'shield_version' => FrameworkIdentity::SHIELD_VERSION,
            'shield_seo_title' => ShieldMetadata::SEO_TITLE,
            'shield_seo_desc' => ShieldMetadata::SEO_DESC,

            // 🧬 Framework Context (Definitions Identity)
            'fw_name' => \Rbn\Framework\Core\Support\Definitions\System\FrameworkIdentity::FRAMEWORK_NAME,
            'fw_version' => \Rbn\Framework\Core\Support\Definitions\System\FrameworkIdentity::FRAMEWORK_VERSION,
            'fw_url' => \Rbn\Framework\Core\Support\Definitions\System\FrameworkIdentity::FRAMEWORK_URL,

            // 👤 Authorship & Site
            'author' => \Rbn\Framework\Core\Support\Definitions\System\FrameworkIdentity::DEVELOPER_NAME,
            'author_url' => \Rbn\Framework\Core\Support\Definitions\System\FrameworkIdentity::DEVELOPER_URL,
            // FW-A0-K1-DEBUG-KAPISI-99 (K-4): Ham $_SERVER['HTTP_HOST'] yerine
            // DOGRULANMIS + kacisli host. Saldirgan kontrollu baslik <head> icine
            // icerik/enjeksiyon sokamaz; gecersiz host'ta site_url yalnizca iz olur.
            'site_url' => self::safeSiteUrl(),

            // 🏮 Assets & Visuals
            'icon' => ShieldMetadata::ICON,
            'favicon' => ShieldMetadata::FAVICON,
            'keywords' => 'rbnframework, error, security, shield, diagnostic, masterpiece'
        ];

        // DUSUK-3: surum bilgisi (framework/shield) yalniz yerel gelistirmede; uretimde marka adi kalir.
        if (!(defined('RBN_DEV') && RBN_DEV === true)) {
            $branding['fw_version'] = '';
            $branding['shield_version'] = '';
        }

        $errorType = $data['type'] ?? 'Hata';

        $shieldVersion = $branding['shield_version'];
        $asset_v = time(); // 🎯 RBN 3.5: Dynamic Masterpiece Cache Buster 🏹🪐

        // 🚀 RBN 3.5: Autonomous Theme JS Discovery (No Hardcode) 🏺⚓
        $jsThemeName = str_replace('_', '-', $view);
        $jsCheckPath = \Rbn\Framework\Core\System\Paths\Paths::framework()->resources('Assets/RbnShield/js/themes/' . $jsThemeName . '.js');
        $shield_theme_js = file_exists($jsCheckPath) ? $jsThemeName : 'standard';

        // Logical Variables for the views
        $error_type = $errorType;
        $error_message = $data['message'] ?? 'Sarsılmaz bir hata oluştu.';
        $solution_hint = $data['hint'] ?? 'Lütfen sistem yöneticisi ile iletişime geçin.';

        // 🏗️ RBN 3.5: Paths Detection via Centralized Hub (No Service Dependency)
        $baseDir = \Rbn\Framework\Core\System\Paths\Paths::framework()->resources('Views/Errors');
        $header = $baseDir . '/Layouts/shield_header.php';
        $viewFile = $baseDir . '/' . ltrim($view, '/') . '.php';
        $footer = $baseDir . '/Layouts/shield_footer.php';

        if (file_exists($viewFile)) {
            // Extract data into local scope for the includes
            extract($branding);
            extract($data);

            if (file_exists($header))
                include $header;
            include $viewFile;
            if (file_exists($footer))
                include $footer;
            exit;
        }

        // 🛡️ RBN 3.5: Masterpiece Recursion Guard 🏹🪐
        // If the 'survival' view itself is missing, we must DIE to prevent an infinite loop.
        if ($view === 'survival') {
            die("=== RBNSHIELD FATAL RECURSION PREVENTED ===\n" .
                "CRITICAL: The 'survival' view file is missing.\n" .
                "DIAGNOSTIC: " . ($data['message'] ?? 'Unknown Error'));
        }

        // Final Bastion: If even the error view is missing, panic to Survival 🆘
        \Rbn\Framework\Core\Services\Exception\Providers\SurvivalProvider::render($errorType, $error_message, $solution_hint, $code);
    }
}
