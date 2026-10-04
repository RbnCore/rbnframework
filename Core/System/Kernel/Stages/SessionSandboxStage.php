<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Kernel\Stages;

use Rbn\Framework\Core\System\Kernel\Kernel;
use Rbn\Framework\Core\System\Kernel\Base\BaseStage;
use Rbn\Framework\Core\System\Paths\Paths;
use Rbn\Framework\Core\Support\Definitions\Route\RouteBlueprint;
use Rbn\Framework\Core\System\Kernel\Bootstrap;
use Rbn\Framework\Core\System\Kernel\Stages\ProjectDiscovery;
use Rbn\Framework\Core\Http\Engine\RequestEnvironment;

/**
 * SessionSandboxStage - Sovereign Session Lifecycle & Project Sandbox 🛡️🛰️⚓
 * 
 * RBN 3.5 Stage: Centralizes session handler registration, garbage collection,
 * and sovereign project switch sync without polluting ComponentRegistry.
 */
class SessionSandboxStage extends BaseStage
{
    public function handle(Kernel $kernel): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        $services = $this->getStageService();
        if (!$services) {
            return;
        }

        $sessionPath = Paths::project()->sessions();

        if (is_dir($sessionPath) && is_writable($sessionPath)) {
            if (session_status() === PHP_SESSION_NONE) {
                // RBN 3.5: Dynamic Session Storage Handler Discovery
                $handler = $services->service('storage') ? $services->service('storage')->sessions() : null;

                if (is_object($handler) && method_exists($handler, 'getName')) {
                    // 🎼 [SOVEREIGN SESSION IDENTITY] 🏙️🛰️⚓
                    session_name($handler->getName());
                    session_set_save_handler($handler, true);
                }

                session_save_path($sessionPath);

                // 🛡️ [GÜVENLİK YAMASI · A0-4] Oturum çerezi bayrakları + oturum
                // sabitleme (session fixation) kapalı.
                //
                // ÖNCEKİ HALİ: yalnız iki `ini_set` vardı (httponly, use_only_cookies).
                // `Secure` ve `SameSite` YOKTU → çerez düz HTTP'ye de gidiyordu ve
                // çapraz site istekleri (CSRF yüzeyi) taşıyabiliyordu.
                // `session.use_strict_mode` 0 idi → **istemcinin gönderdiği oturum
                // ID'si kabul ediliyordu**: saldırgan bir ID'yi önceden belirleyip
                // kurbanın oturumuna yapıştırabiliyor (A0-4 / ONARIM-PLANI §2).
                //
                // KURALLAR:
                //  - use_strict_mode=1  → istemcinin gönderdiği bilinmeyen ID REDDEDİLİR
                //                         (oturum sabitleme için asıl satır).
                //  - use_only_cookies=1 → ID yalnız çerezle taşınır (URL'de değil).
                //  - use_trans_sid=0    → çerez yoksa PHP geçici oturum çerezi üretmez.
                //  - cookie_httponly=1   → JS erişemez (XSS ile çerez çalınması).
                //  - cookie_secure       → yalnız HTTPS'te 1. Yerel HTTP'de 0 kalır;
                //                         aksi halde yerel giriş testleri kırılırdı.
                //  - cookie_samesite=Lax→ çapraz-site POST'larda çerez gitmez
                //                         (GET ile link tıklama korunur → geri alınabilirlik).
                ini_set('session.use_strict_mode', '1');
                ini_set('session.cookie_httponly', '1');
                ini_set('session.use_only_cookies', '1');
                ini_set('session.use_trans_sid', '0');
                ini_set('session.cookie_samesite', 'Lax');
                ini_set('session.cookie_secure', $this->isHttpsRequest() ? '1' : '0');
                // Hata ayıklama sırasında "fazla hata" çerezinin taşınmasını engelle.
                ini_set('session.cookie_path', '/');

                // 🛡️ [RBN SESSION POLICY] Cerezsiz makine-arasi isteklerde (ajan / webhook / WS)
                // oturum acmaz. Politika: projenin Core/Config/session-policy.php dosyasi.
                // Politika yoksa / okunamazsa / hata verirse davranis DEGISTIRILMEZ.
                if ($this->isSessionPolicyExempt($handler)) {
                    return;
                }

                // [RBN PROTECT] Session Sandbox Initialization 🛡️
                try {
                    @session_start();

                    // 10% Probability Garbage Collection for expired session files 🧹
                    if (random_int(1, 10) === 1) {
                        if (is_object($handler) && method_exists($handler, 'gc')) {
                            $handler->gc(86400); // Clean files older than 24 hours
                        } else {
                            // [S-05] GC KAZANILDI ama handler `gc()` metodunu TASIYAMIYOR.
                            // Eski kodda bu durum SESSIZCE gecerdi: oturum dosyalari
                            // birikir, hicbir gunluk/ipucu olusmaz; S-04'un olculdugu
                            // maliyet sorunu da bu yuzden fark edilemez.
                            // Gunluga TEK uyari; istek ASLA dusurulmez (fail-soft).
                            error_log('[RBN-KERNEL] Oturum GC atlandi: handler gc() desteklemiyor ('
                                . (is_object($handler) ? get_class($handler) : gettype($handler)) . ')');
                        }
                    }

                    // 🎼 [SOVEREIGN PROJECT SWITCH SYNC] 🔄🛰️⚓
                    $this->syncActiveProjectContext();
                } catch (\Throwable $e) {
                    // Fail-safe graceful catch
                }
            }
        }
    }

    /* =========================================================================
     | [GÜVENLİK · A0-4] İstek HTTPS mi? `session.cookie_secure` kararının
     | TEK kaynağı burasıdır.
     |
     | NEDEN `$_SERVER['HTTPS']` TEK BAŞINA YETMEZ: nginx/php-fpm arkasında
     | HTTPS istekleri `fastcgi_param HTTPS on;` ile gelir; ama bazı kurulumlarda
     | (HTTP/2, load-balancer, yerel PHP sunucusu) bu parametre boş gelir ve
     | `$_SERVER['SERVER_PORT']` da güvenilir sinyal olur. İkisi de "güvenli"
     | sayılmıyorsa `X-Forwarded-Proto` kontrol edilir — **güvenilirliği
     | varsayılan olarak DÜŞÜK**: istemci başlığı taklit edebilir, bu yüzden
     | yalnız `https` DEGERİ kabul edilir ve sonuç yine de `Secure`'ın **AÇMA**
     | yönüne gider (yanlış negatif = yerel HTTP'de çerez düşer, saldırı değil).
     |
     | Ters yön (düz HTTP'de Secure=1) bir güvenlik açığı değil, kırılan test
     | olurdu; ONARIM-PLANI A0-4 bu yüzden "yerel HTTP'de çerez düşer" notunu
     | bir kırılma değil, ortam koşulu sayar.
     * ========================================================================= */
    private function isHttpsRequest(): bool
    {
        // [http #11] Mantık `RequestEnvironment` içine taşındı (tek doğruluk
        // kaynağı); burada aynı sonuç döner, davranış DEĞİŞMEDİ.
        return RequestEnvironment::isHttpsRequest();
    }

    /* =========================================================================
     | [RBN SESSION POLICY] Oturum acma muafiyeti (cerezsiz makine-arasi istekler)
     | EKLEMELI + MINIMAL: mevcut hicbir davranis degistirilmez.
     | Politika dosyasi: <proje>/Core/Config/session-policy.php
     | Sema: return ['<project_key>' => ['skip_no_cookie' => [...], 'hosts' => ['<host>' => ['skip_no_cookie' => [...]]]]]
     ========================================================================= */

    /** @var array<int,string> Tum dosya okuma sonuclari (anahtarli onbellek) */
    private static array $sessionPolicyCache = [];
    private static bool $sessionPolicyLoaded = false;

    /**
     * Bu istek "oturum acmaya muaf" mi?
     *
     * KURALLAR:
     *  - Oturum cerezi (session_name) geliyorsa ASLA muaf degildir.
     *  - Politika dosyasi/anahtari/listesi yoksa, bozuksa, hata verirse -> ESKI DAVRANIS.
     *  - Host altinda tanim varsa SADECE o host'un listesi gecerli (proje listesiyle birlestirilmez).
     *  - Yol eslesmesi segment sinirli on ek + normalize (sorgu dizesi, //, . ve .., % kodlama).
     */
    private function isSessionPolicyExempt(mixed $handler = null): bool
    {
        if (session_status() !== PHP_SESSION_NONE || headers_sent()) {
            return false;
        }

        // 1) Oturum cerezi varsa hicbir zaman muaf tutulmaz (giris yapmis kullanici bozulmaz).
        $cookieNames = [];
        if (is_object($handler) && method_exists($handler, 'getName')) {
            try {
                $name = (string) $handler->getName();
                if ($name !== '') {
                    $cookieNames[] = $name;
                }
            } catch (\Throwable $e) {
                // yoksay
            }
        }
        $cookieNames[] = (string) session_name();
        foreach (array_unique($cookieNames) as $cookieName) {
            if ($cookieName !== '' && isset($_COOKIE[$cookieName]) && (string) $_COOKIE[$cookieName] !== '') {
                return false;
            }
        }

        // 2) Politika dosyasi (yoksa/hataliysa ESKI DAVRANIS).
        $policy = self::loadSessionPolicy();
        if ($policy === null) {
            return false;
        }

        // 3) Proje anahtari (framework'un kullandigi anahtar).
        $projectKey = '';
        if (function_exists('active_project_key')) {
            $projectKey = (string) active_project_key();
        }
        if ($projectKey === '' && function_exists('project_key')) {
            $projectKey = (string) project_key();
        }
        if ($projectKey === '' || !isset($policy[$projectKey]) || !is_array($policy[$projectKey])) {
            return false;
        }
        $entry = $policy[$projectKey];

        // 4) Host bazli alt kiracilar: eslesme varsa yalnizca o host'un listesi gecerli.
        $prefixes = null;
        $hosts = $entry['hosts'] ?? null;
        if ($hosts !== null) {
            if (!is_array($hosts)) {
                return false; // YANLiS TiP -> ESKI DAVRANIS
            }
            if ($hosts !== []) {
                $currentHost = $this->normalizeSessionHost((string) ($_SERVER['HTTP_HOST'] ?? ''));
                foreach ($hosts as $hostKey => $hostEntry) {
                    if ($this->normalizeSessionHost((string) $hostKey) === $currentHost) {
                        $prefixes = is_array($hostEntry) ? ($hostEntry['skip_no_cookie'] ?? null) : null;
                        break;
                    }
                }
            }
        }
        if ($prefixes === null) {
            $prefixes = $entry['skip_no_cookie'] ?? null;
        }
        if ($prefixes === null || !is_array($prefixes) || $prefixes === []) {
            return false;
        }

        // 5) Segment sinirli on ek eslesmesi.
        $path = $this->normalizeRequestPath();
        foreach ($prefixes as $prefix) {
            if ($prefix === null) {
                continue; // bos giris yoksayilir
            }
            if (!is_string($prefix)) {
                return false; // YANLiS TiP -> ESKI DAVRANIS
            }
            if ($this->pathMatchesPrefix($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * session-policy.php dosyasini istek basina EN FAZLA BIR kez guvenli sekilde okur.
     * Dosya yoksa / dizi degilse / hata olursa null doner (cagiran taraf ESKI DAVRANIS'a doner).
     */
    private static function loadSessionPolicy(): ?array
    {
        $file = null;
        try {
            if (Paths::isInitialized()) {
                $file = Paths::project()->configs('session-policy.php');
            }
        } catch (\Throwable $e) {
            $file = null;
        }

        if ($file === null) {
            return null;
        }

        if (self::$sessionPolicyLoaded) {
            return self::$sessionPolicyCache[$file] ?? null;
        }
        self::$sessionPolicyLoaded = true;

        if (!is_file($file) || !is_readable($file)) {
            self::$sessionPolicyCache[$file] = null;
            return null;
        }

        try {
            $data = require $file;
            if (!is_array($data) || $data === []) {
                $data = null;
            }
        } catch (\Throwable $e) {
            $data = null;
        }

        return self::$sessionPolicyCache[$file] = $data;
    }

    /**
     * Istek yolunu guvenli hale getirir: sorgu dizesi atilir, // tekillestirilir,
     * yuzde kodlama cozulur (%2e%2e tuzagi), "." / ".." segmentleri cozulur.
     * Buyuk/kucuk harf DUYARLIDIR (yollar duyarlidir).
     */
    private function normalizeRequestPath(): string
    {
        $uri  = (string) ($_SERVER['REQUEST_URI'] ?? '');

        // parse_url() "//api/agent" gibi bir yolu "host" sanip yolu BOS dondurur.
        // Bu yuzden yol once elle kirpilir, sonra normalize edilir.
        $path = substr($uri, 0, strcspn($uri, '?#'));

        if ($path === '') {
            return '/';
        }

        $path = str_replace('\\', '/', rawurldecode($path));

        $segments = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($segments);
                continue;
            }
            $segments[] = $segment;
        }

        return $segments === [] ? '/' : '/' . implode('/', $segments);
    }

    /**
     * Segment sinirli on ek eslesmesi:
     *   '/api/agent'  -> '/api/agent' ve '/api/agent/logs' ESLESIR
     *   '/api/agent'  -> '/api/agentx' ESLESMEZ
     */
    private function pathMatchesPrefix(string $path, string $prefix): bool
    {
        $needle = '/' . trim($prefix, '/');
        if ($needle === '/') {
            return false;
        }

        return $path === $needle || str_starts_with($path, $needle . '/');
    }

    /**
     * Host normalizasyonu: kucuk harf, port atimi, "www." atimi, sondaki nokta atimi.
     */
    private function normalizeSessionHost(string $host): string
    {
        $host = strtolower(trim($host));
        if ($host === '') {
            return '';
        }
        if (str_contains($host, ':')) {
            $host = explode(':', $host)[0];
        }
        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        $host = rtrim($host, '.');

        // Güvenilmeyen Host başlığı: geçerli alan adı karakteri dışındakiler
        // eşleşmeye girmez (S-02).
        return preg_match('/^[a-z0-9.-]+$/', $host) === 1 ? $host : '';
    }

    /**
     * Sadece admin/dashboard paneli isteklerinde session'daki seçili projeyi bağlar.
     * Ön yüz (frontend) istekleri her zaman kendi domaininin projesini çalıştırır. 🛡️
     */
    private function syncActiveProjectContext(): void
    {
        $uriPath = '/' . trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
        $isAdminRequest = false;

        $loginPath = RouteBlueprint::LOGIN_PATH ?? 'rbn-admin';
        $dashDefault = RouteBlueprint::DASHBOARD_PREFIX ?? 'dashboard';
        $dashPrefix = function_exists('project_data') ? project_data('dashboard_prefix') : null;

        $adminPrefixes = array_unique(array_filter([
            $loginPath,
            $dashDefault,
            !empty($dashPrefix) && $dashPrefix !== 'user' ? trim((string) $dashPrefix, '/') : null,
            ...RouteBlueprint::CORE_MODULES,
            ...RouteBlueprint::AUTH_ROOTS,
        ]));

        foreach ($adminPrefixes as $prefix) {
            $prefixPath = '/' . ltrim($prefix, '/');
            if ($uriPath === $prefixPath || str_starts_with($uriPath, $prefixPath . '/')) {
                $isAdminRequest = true;
                break;
            }
        }

        if ($isAdminRequest && !empty($_SESSION['active_project_key'])) {
            $switchedKey = (string) $_SESSION['active_project_key'];
            Bootstrap::setAppContext('project_key', $switchedKey);
            $pData = ProjectDiscovery::getProjectData(null, $switchedKey);
            if (!empty($pData)) {
                Bootstrap::setAppContext('project_data', $pData);
            }
        }
    }
}
