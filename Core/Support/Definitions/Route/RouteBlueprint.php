<?php

namespace Rbn\Framework\Core\Support\Definitions\Route;

use Rbn\Framework\Core\Base\Data\BaseConfig;
use Rbn\Framework\Bundles\RbnSuite\RbnAuth\Middleware\AuthMiddleware;

/**
 * RouteBlueprint - Centralized Routing Blueprint ⚖️🏛️🗺️
 * 
 * RBN Framework: Master blueprint for routing rules, middleware groups,
 * and core engine patterns. Elevates these definitions to the Support layer.
 */
class RouteBlueprint extends BaseConfig
{
    /**
     * Explicit Definition Identity 🧬🏛️
     */
    protected static ?string $definitionCategory = 'route';

    /**
     * Middleware Aliases & Groups 🛡️
     */
    /**
     * Middleware Aliases & Groups 🛡️
     *
     * [FW-APIGUARD · TASARIM GOREV 1 · 2026-10-03 · team member]
     * `machine-api` alias'i eklendi. Yazim: `Route::middleware('machine-api')`
     * (PARAMETRESIZ). Neden parametre yok:
     * `Dispatcher::resolveMiddleware()` (`Core/Routes/Engine/Dispatcher.php:113`)
     * `alias:arg` bicimini COZMEZ — cozulemeyen middleware icin R-10
     * fail-closed `RuntimeException` atar, yani `machine-api:<kapsam>`
     * yazimi rotayi 500 ile KIRARDI. O dosya bu gorevin dosya kumesinde
     * DEGILDIR; parametre destegi ayri bir is olarak birakilir.
     *
     * IP katmani muafiyeti `MachineApiRegistry` tohum tablosundan gelir
     * (IP kontrolu rotalar yuklenmeden ONCE calistigi icin bkz. sinif
     * ustundeki kernel sira uyarisi); bu alias `ApiGuard` tarafini besler.
     */
    public const MIDDLEWARE = [
        'aliases' => [
            'auth' => AuthMiddleware::class,
            'guard' => AuthMiddleware::class,
            'machine-api' => \Rbn\Framework\Core\Http\Security\ApiGuard::class,
        ],
        'groups' => [
            'user' => ['auth', 'role:user'],
            'admin' => ['auth', 'role:admin'],
            'superadmin' => ['auth', 'role:superadmin'],
            'developer' => ['auth', 'role:developer'],
        ],
    ];

    /**
     * System & Project Route Mappings 🗺️
     */
    public const FILES = [
        'framework' => [
            'core',
            'auth',
            'web',
        ],
        'project' => [
        ],
    ];

    /**
     * Dashboard & Admin Panel Roles 🛡️
     */
    public const PANELS = ['user', 'admin', 'developer', 'guest', 'frontend'];

    /**
     * Default Login Path 🚀
     */
    public const LOGIN_PATH = 'rbn-admin';

    /**
     * Çekirdek web rotasının panel KAPALIYKEN yönlendirdiği giriş takma adı.
     * `AUTH_ROOTS` içinde DEĞİLDİR (oradaki tüketiciler öneklemeyi/oturumu bu
     * adla ilişkilendirmez); bu ad yalnız "panel kapalı" kapsamında ve sitemap
     * süzgecinde kullanılır.
     */
    public const LOGIN_ALIAS = 'giris';

    /**
     * Auth-related Root Paths (Prevents prefixing) 🔒
     */
    public const AUTH_ROOTS = [
        self::LOGIN_PATH,
        'register',
        'auth',
        'logout',
        'lockscreen',
        'forgot-password',
        'verify-code',
        'reset-password'
    ];

    /**
     * Core Framework Modules (No prefix required) 📦
     */
    public const CORE_MODULES = ['dashboard', 'RbnAdmin'];

    /**
     * Default Dashboard Prefix 🚀
     */
    public const DASHBOARD_PREFIX = 'dashboard';

    /**
     * Critical System Paths Allowed to Bypass Maintenance Mode 🛡️🔒
     */
    public const SYSTEM_ALLOWED_PATHS = [
        self::LOGIN_PATH,
        'auth',
        'logout',
        'api'
    ];

    /**
     * Honeypot & Attack Scanner Paths to Permanently Redirect to Home (/) 🍯🛑
     */
    public const HONEYPOT_PATHS = [
        '/home',
        '/main',
        '/login',
        '/giris',
        '/wp-login.php',
        '/xmlrpc.php',
        '/wp-admin',
        '/wordpress',
        '/backup',
        '/old',
        '/new',
        '/wiki',
        '/.well-known/ucp',
        '/.well-known/traffic-advice',
        '/favicon.png',
        '/sitemap_index.xml',
        '/app-ads.txt',
        '/admin',
        '/media/system/js/core.js',
        '/wp',
        '/wp-old',
        '/oldsite',
        '/old-site',
        '/test',
        '/demo',
        '/bc',
        '/www',
        '/bk',
        '/bak',
        '/bac',
        '/sito',
        '/site',
        // '/sayfa' KALDIRILDI (FW-ROBOTS-SAYFA-101): bu bir tuzak değil, framework'ün
        // KENDİ gerçek içerik rota öneki (Mappings/web.php: Route::get('sayfa/{slug}')).
        // Bu liste RedirectManager'da TAM eşleşmeyle (in_array) kullanılır; robots.txt'te
        // ise RobotsConfig::CORE_DISALLOWS içine yayılır ve Google ÖN-EK eşleşmesi
        // yapar. '/sayfa' oradaydı -> tum /sayfa/<slug> yasal sayfalari (kvkk, gizlilik)
        // tarayiciya kapali kaliyordu. Tek merkez: liste buradan temizlenir, ikinci bir
        // istisna listesi yazilmaz.
        '/ip',
        '/.well-known/app-ads.txt',
        '/images/default-avatar.png'
    ];

    /**
     * [R-02] `HONEYPOT_PATHS` içindeki **uygulama önekleri**: bu adresler bir
     * projede GERÇEK sayfa olabilir (`/test`, `/demo`, `/admin`, `/site`, `/ip`).
     *
     * SORUN: `HONEYPOT_PATHS` TAM eşleşmeyle (`in_array`) `redirectHome()`
     * içinde kullanılıyordu; yani `/test` gerçek bir sayfa olsa bile istek
     * 301 ile ana sayfaya düşüyordu (o site için kalıcı 404 gibi görünür).
     * Önekleri LİSTEDEN SİLMEK de güvenli değildir: her proje için ayrı ayrı
     * davranış değiştirirdi (brif notu).
     *
     * GÜVENLİ YOL (karar tablosu A-07'in önerisi): liste DOKUNULMAZ, yalnız
     * bu önekler için "bu yol gerçek bir rotaya eşleşiyor mu" kontrolü yapılır.
     * `Router`/`Matcher` bu noktada hazırdır (`Routing` aşaması
     * `Route::loadRoutes()`'i çoktan çalıştırmıştır, `Route::run()` içindeki
     * `RedirectManager::process()` ondan SONRA gelir).
     *
     *   - gerçek rota EŞLEŞİYORSA → tuzak DEĞİLDİR, yönlendirme yapılmaz
     *   - eşleşme YOKSA → eski davranış BİREBİR korunur (301 → `/`)
     *
     * KAPSAM: yalnız TAM eşleşen `HONEYPOT_PATHS` girdileri. Alt yollar
     * (`/test/foo`) zaten bu listede yok; `HONEYPOT_KEYWORDS` (alt dize),
     * eski font ve `.php/.html/.htm` kuralları DEĞİŞTİRİLMEDİ.
     *
     * `/www` bu listeye YAZILMAZ: o bir yol değil, HOST önekidir ve
     * `RedirectManager::removeWww()` tarafından zaten ele alınır.
     */
    public const HONEYPOT_ROUTE_EXEMPT = [
        '/admin',
        '/test',
        '/demo',
        '/site',
        '/ip',
    ];

    /**
     * Honeypot & Attack Scanner Keywords (substrings) to check in URI paths to Redirect to Home (/) 🍯🔍
     */
    public const HONEYPOT_KEYWORDS = [
        'wp-includes',
        'wp-content',
        'wlwmanifest.xml',
        'license.txt',
        'wp/v2',
        'wp-json',
        'enclosure',
        'sppagebuilder',
        'plugins/system',
        'helix3',
        'astroid',
        'comments/feed'
    ];

    /**
     * Legacy webfont keywords to redirect to home to prevent 404 logs (excluding framework-assets) 🔤
     */
    public const LEGACY_FONTS_KEYWORDS = [
        'webfonts',
        'fonts/',
        'remixicon'
    ];

    /**
     * Standard asset paths for fallback redirections (Self-Healing) 🩹
     */
    public const FALLBACK_FAVICONS = [
        'favicon.ico',
        'favicon.png',
        'favicon.svg',
        'images/favicon.ico',
        'images/favicon.png',
        'images/favicon.svg'
    ];

    public const FALLBACK_MOBILE_PREFIXES = [
        'apple-touch-icon',
        'android-chrome'
    ];

    public const FALLBACK_MOBILE_MANIFESTS = [
        'browserconfig.xml',
        'site.webmanifest'
    ];

    public const FALLBACK_OG_IMAGES = [
        'og-image.png',
        'og-image.jpg',
        'og-image.jpeg',
        'images/og-image.png',
        'images/og-image.jpg',
        'images/og-image.jpeg',
        'images/share.png',
        'images/share.jpg'
    ];

    /**
     * Corporate & Legal Page URL Aliases ⚖️
     */
    public const LEGAL_ALIASES = [
        'kvkk' => 'kvkk-aydinlatma-metni',
        'iptal-iade' => 'iptal-iade-kosullari',
        'iade' => 'iptal-iade-kosullari',
        'iptal-ve-iade-kosullari' => 'iptal-iade-kosullari',
        'teslimat' => 'teslimat-kargo',
        'kargo' => 'teslimat-kargo',
        'teslimat-ve-kargo' => 'teslimat-kargo',
        'gizlilik' => 'gizlilik-politikasi',
        'gizlilik-ve-guvenlik' => 'gizlilik-guvenlik-politikasi',
        'gizlilik-ve-guvenlik-politikasi' => 'gizlilik-guvenlik-politikasi',
        'cerezler' => 'cerez-politikasi',
        'cerez' => 'cerez-politikasi',
        'cookie' => 'cerez-politikasi',
        'kosullar' => 'kullanim-kosullari',
        'terms' => 'kullanim-kosullari',
        'mesafeli-satis' => 'mesafeli-satis-sozlesmesi',
        'sozlesme' => 'kullanici-sozlesmesi',
    ];
}
