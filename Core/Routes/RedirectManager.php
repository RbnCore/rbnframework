<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Routes;

use Rbn\Framework\Core\Base\Services\BaseManager;
use Rbn\Framework\Core\Routes\Engine\Matcher;
use Rbn\Framework\Core\Routes\Engine\Providers\Router;
use Rbn\Framework\Core\Support\Definitions\Route\RouteBlueprint;
use Rbn\Framework\Core\Base\Services\Traits\Service\Blogcontent\ContentLegacyRedirectServiceTrait;
use Rbn\Framework\Core\Http\Engine\Traits\Response\RedirectTrait;
use Rbn\Framework\Core\Support\Definitions\Render\AssetConvention;

/**
 * RedirectManager - Extensible Request & URL Normalization Engine 🌐🔄⚓
 * 
 * RBN 3.5 Masterpiece: Evaluates and handles web redirects using an extensible rule pipeline.
 * Collects modifications from all rules and performs exactly ONE single 301 redirect.
 */
class RedirectManager extends BaseManager
{
    use ContentLegacyRedirectServiceTrait;

    /**
     * [GUVENLIK / FW-110 A-2] TEK merkez: `Location` hedefi buradan gecer.
     *
     * Bu sinif `RedirectTrait::guvenliHedef()`'i HIC KULLANMIYORDU; kendi
     * `header("Location: ".$url)`'ini yaziyordu ve `buildUrl()` hedef host'u
     * HAM `$_SERVER['HTTP_HOST']`'tan (yani istemciden) aliyordu. Sonuc:
     * guvenilir olmayan bir `Host` basligi ile gelen istek, kanonik/honeypot
     * 301'inde SALDIRGANIN HOSTUNA tasiniyordu (acik yonlendirme, güvenlik incelemesi 104).
     *
     * Trait'in `redirect()` metodu ile isim cakisiyor; PHP'de SINIFIN kendi
     * metodu trait metodunu YERER (cakisma hatasi olmaz), yani asagidaki
     * `redirect()` bizimki olarak kalir ve dogrulamayi kendisi cagirir.
     */
    use RedirectTrait;
    /**
     * Rules to execute sequentially.
     * New rules can be appended here.
     * 
     * @var string[]
     */
    protected array $rules = [
        'enforceCanonicalDomain',
        'forceHttps',
        'removeWww',
        'removeTrailingSlash',
        'forceLowercaseUrl',
        'redirectOldUrls',
        'redirectHome',
        'selfHealingAssets',
    ];

    /**
     * [FW-ACIL-MEDYA-301] `forceLowercaseUrl()` kuralindan MUAF yol ONEKLERI.
     *
     * SORUN: kural her yolu `mb_strtolower()` ile kucuk harfe cevirip tek bir 301
     * yaziyordu. Boylece BUYUK/KUCUK HARFE DUYARLI, opak (encoded) segment tasiyan
     * yollar calinca hedef COZULEMEZ ve AssetController 404 donuyordu. Canli kanit
     * (yalniz GET): `/media/series/aHR0cHM6.../5ZgPQjEsJqCwRtx6vsBBISxn389`
     * -> 301 Location: kucuk harfli karsilik -> o adres 404.
     *
     * COZUM: muafiyet listesi TEK YERDE, DEGISMEZ (const) tutulur; asagida
     * ONEK + segment siniri denetimi vardir (`/media-arsivi` gibi onek-benzeri
     * yollar muaf OLMAZ, boylece SEO kanonikalizasyonu daralmaz).
     *
     * KAPSAM (olcumle secildi, her yol bir rota oneki veya opak yol tasiyicisidir):
     *   - `media`     : `ViewHelperTrait::mediaUrl()` base64 masked medya
     *   - `fw-proxy`  : `FileProxyController` upload/export dosya adlari
     *   - `api`, `webhook`, `bot-sync`, `bot-data`: zaten `shouldBypassRedirect()`
     *     ile 301 disi; liste birlik/kesinlik icin tamamlanir.
     * Geri kalan her yol (sayfa, blog, dizi, ara ...) kanonik kucuk harf 301'i
     * almaya DEVAM eder — SEO davranisi bu degisiklikle korunur.
     *
     * @var string[] Kucuk harf kanonikalizasyonundan muaf yol onekleri
     */
    protected const LOWERCASE_EXEMPT_PREFIXES = [
        '/framework-assets',
        '/project-assets',
        '/media',
        '/fw-proxy',
        '/api',
        '/webhook',
        '/bot-sync',
        '/bot-data',
    ];

    /** @var string Active scheme (http/https) */
    protected string $scheme = 'http';

    /** @var string Active host/domain name */
    protected string $host = '';

    /** @var string Active URI path (e.g. /blog) */
    protected string $path = '';

    /** @var string Active query string parameters (without ?) */
    protected string $queryString = '';

    /**
     * Process all registered redirect rules 🔄
     */
    public function process(): void
    {
        // Skip CLI requests
        if (PHP_SAPI === 'cli') {
            return;
        }

        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $parsedUrl = parse_url($requestUri);

        $this->scheme = $this->isHttps() ? 'https' : 'http';
        $this->host = $_SERVER['HTTP_HOST'] ?? '';
        $this->path = $parsedUrl['path'] ?? '';
        $this->queryString = $parsedUrl['query'] ?? '';

        // API ve Webhook çağrıları kesinlikle yönlendirmeye (redirect) uğramamalıdır 📡🛡️
        if ($this->shouldBypassRedirect()) {
            return;
        }

        $initialUrl = $this->buildUrl();

        // Pass request parameters through all rules
        foreach ($this->rules as $rule) {
            if (method_exists($this, $rule)) {
                $this->{$rule}();
            }
        }

        $finalUrl = $this->buildUrl();

        // Perform exactly one redirect if the URL was normalized/changed
        if ($initialUrl !== $finalUrl) {
            $this->redirect($finalUrl);
        }
    }

    /**
     * [GUVENLIK / FW-110 A-2] 301 hedefini ayni-origin'e gore denetler.
     *
     * DOGRULAMA TEK KAYNAKTAN: `RedirectTrait::guvenliHedef()` (beyaz liste =
     * aktif projenin kanonik alan adi + grup alan adlari + KOKE GORE DOGRULANMIS
     * istek host'u). Burada YENI bir yardimci/helper yazilmadi.
     *
     * Port kurali bu sinifta yumusatilir: dogrulama ORIGIN (host) uzerinden
     * yapilir, PORT korunur. Aksi halde yerel gelistirmede yaygin olan
     * `Host: proje.test:8080` kacisi ("beyaz listede port acik yazili degil")
     * REDDEDILIR ve HER canonical/honeypot 301'i goreli `/`ye dustu.
     *
     * @return string guvenli hedef (`/` = guvenli varsayilan)
     */
    protected function guvenliYonlendirmeHedefi(string $url): string
    {
        $origin = $this->portSoyulmusUrl($url);
        $karar   = $this->guvenliHedef($origin);

        // guvenliHedef() ya girdiyi OLDUGU gibi gecirir ya da '/'e dusurur.
        if ($karar !== $origin) {
            return '/';
        }

        return $url;
    }

    /**
     * [GUVENLIK] URL'den acik yazili portu soyup yalnizca kaynagi (origin)
     * birakir: `https://host:8080/x` -> `https://host/x`.
     */
    private function portSoyulmusUrl(string $url): string
    {
        if (!preg_match('~^(https?)://([^/:]+):(\d{1,5})(/.*)?$~i', $url, $m)) {
            return $url;
        }

        return $m[1] . '://' . $m[2] . ($m[4] ?? '');
    }

    /**
     * Reconstruct the absolute URL from current state 🌐
     */
    protected function buildUrl(): string
    {
        $url = $this->scheme . '://' . $this->host . $this->path;
        if (!empty($this->queryString)) {
            $url .= '?' . $this->queryString;
        }
        return $url;
    }

    /**
     * Rule: Redirect staging/dev domains (e.g. *.rbncore.tr or non-canonical hosts) directly to official primary domain home/URL 🛡️🏠
     */
    protected function enforceCanonicalDomain(): void
    {
        // [GUVENLIK / FW-110 A-2] Istek `Host`'u normalize edilmeden kiyaslanmaz:
        // port (`proje.test:8080`), buyuk harf (`PROJE.TEST`) ve sondaki nokta
        // normalize `PreBoot::normalizeHost()` ile TEK merkezden indirgenir.
        // Gecersiz/asinir girdi bos string doner (fail-closed: kanonik reset).
        $currentHost = class_exists(\Rbn\Framework\Core\System\Kernel\Base\PreBoot::class)
            ? \Rbn\Framework\Core\System\Kernel\Base\PreBoot::normalizeHost($this->host)
            : rtrim(strtolower($this->host), '.');
        $projectData = \Rbn\Framework\Core\System\Kernel\Bootstrap::getAppContext('project_data') ?: [];
        $officialDomain = strtolower((string) ($projectData['domain'] ?? ''));

        if (empty($officialDomain)) {
            return;
        }

        // Clean domain if it contains scheme
        if (str_starts_with($officialDomain, 'http://') || str_starts_with($officialDomain, 'https://')) {
            $officialDomain = parse_url($officialDomain, PHP_URL_HOST) ?? $officialDomain;
        }

        // [FW-110 A-2] Kanonik alan adi da ayni normalize merkezinden gecer
        // (sondaki nokta / port / buyuk harf).
        $officialDomain = class_exists(\Rbn\Framework\Core\System\Kernel\Base\PreBoot::class)
            ? \Rbn\Framework\Core\System\Kernel\Base\PreBoot::normalizeHost($officialDomain)
            : rtrim(strtolower($officialDomain), '.');

        // FW-A0-K1-DEBUG-KAPISI-99 (A-2, K-1 kopyasi): `str_contains($host,'localhost')`
        // ALT DIZGE kontrolu kaldirildi — `x.localhost.alanadi` artik yerel sayilmaz.
        // Ortam karari ARTIK TEK KAYNAKTAN gelir: PreBoot::detectEnvironment() -> RBN_DEV
        // (fail-closed: tam eslesme veya `*.test` TAM son-ek + loopback/ozel ag istemci).
        // RBN_DEV tanimli degilse (CLI/birim testi) yalnizca TAM son-ek + beyaz liste.
        if (defined('RBN_DEV')) {
            $isLocalDev = RBN_DEV;
        } else {
            $isLocalDev = str_ends_with($currentHost, '.test')
                || str_ends_with($currentHost, '.local')
                || in_array($currentHost, \Rbn\Framework\Core\System\Kernel\Base\PreBoot::LOCAL_HOST_ALLOWLIST, true);
        }

        // [SEO-ROBOTS-SITEMAP-301] APEX `rbncore.tr` DALI KALDIRILDI.
        //
        // SORUN: `str_ends_with($currentHost, '.rbncore.tr')` alt-dizge kontrolu
        // apex'i ZATEN yakalamaz. `|| $currentHost === 'rbncore.tr'` eklenince
        // apex de "kanonik disi (staging) host" sayildi ve asagidaki blok
        // `$this->path = '/'` yazdigindan `rbncore.tr` uzerindeki TUM yollar
        // (robots.txt, sitemap.xml, gercek sayfalar, sahte yollar) 301 -> `/`
        // oldu. Canli kanit (yalniz GET, 05.10.2026):
        //   /robots.txt  -> 301 Location: https://rbncore.tr/  (g├Âvde 0 bayt)
        //   /sitemap.xml -> 301 Location: https://rbncore.tr/ (g├Âvde 0 bayt)
        //   /referanslar -> 301 -> /   (sitede GERCEKTEN var olan sayfa)
        // Arama motoru robots.txt/sitemap.xml'e ULASAMIYOR.
        //
        // NEDEN YANLIS: `rbncore.tr` staging alt domaini DEGIL, `rbncore`
        // projesinin KANONIK alan adidir (canli `projects` satiri id=4:
        // project_key=`rbncore`, domain=`rbncore.tr`). Kural kendi kanonik alan
        // adini kanonik disi ilan ediyordu.
        //
        // GERI ALINAN DAVRANIS: alt-dizge dali `.rbncore.tr` OLDUGU GIBI
        // KORUNUR; `email.rbncore.tr` gibi alt alan adlari ana sayfaya 301'lemeye
        // DEVAM eder (kasintili kural). Yalnizca apex muaf kalir.
        $isRbnCore = str_ends_with($currentHost, '.rbncore.tr') && !$isLocalDev;

        if ($isRbnCore || (!$isLocalDev && $currentHost !== $officialDomain && $currentHost !== ('www.' . $officialDomain))) {
            $this->host = $officialDomain;
            $this->path = '/';
            $this->queryString = '';
        }
    }

    /**
     * Rule: Enforce HTTPS on non-local environments 🔒
     */
    protected function forceHttps(): void
    {
        if (defined('RBN_DEV') && !RBN_DEV) {
            if (!$this->isHttps()) {
                $this->scheme = 'https';
            }
        }
    }

    /**
     * Rule: Remove www prefix and redirect to non-www canonical domain 🌐
     */
    protected function removeWww(): void
    {
        if (str_starts_with(strtolower($this->host), 'www.')) {
            $this->host = substr($this->host, 4);

            // Force HTTPS in production when stripping WWW
            $isDev = defined('RBN_DEV') && RBN_DEV;
            if (!$isDev) {
                $this->scheme = 'https';
            }
        }
    }

    /**
     * Rule: Remove trailing slash from URLs (excluding root /) to enforce canonical SEO paths 🌐
     */
    protected function removeTrailingSlash(): void
    {
        if ($this->path !== '/' && str_ends_with($this->path, '/')) {
            $this->path = rtrim($this->path, '/');
        }
    }

    /**
     * Rule: Force lowercase on URI path (excluding query string) for standard SEO canonicalization 🌐
     */
    protected function forceLowercaseUrl(): void
    {
        // [FW-ACIL-MEDYA-301] Muafiyet TEK listeden okunur (`LOWERCASE_EXEMPT_PREFIXES`).
        // Segment siniri: `/media` ile `/media-arsivi` AYRI yollardir; onek-benzeri
        // bir yol muaf sayilmaz, boylece normal sayfa yollari kanonik kucuk harf
        // 301'ini almaya devam eder (SEO davranisi korunur).
        if ($this->isLowercaseExemptPath($this->path)) {
            return;
        }

        $lowerPath = mb_strtolower($this->path, 'UTF-8');
        if ($this->path !== $lowerPath) {
            $this->path = $lowerPath;
        }
    }

    /**
     * [FW-ACIL-MEDYA-301] Yol, kucuk harf kanonikalizasyonundan muaf mi?
     *
     * Kural basit: yol, `LOWERCASE_EXEMPT_PREFIXES` girdilerinden biriyle TAM
     * eslesirse ya da o onek + `/` ile baslarsa muaftir. Boylece `/media` ve
     * `/media/...` muaf olurken `/media-arsivi` (onek-benzeri, normal sayfa yolu)
     * muaf OLMAZ ve kanonik kucuk harf 301'ini almaya devam eder.
     *
     * Buyuk/kucuk harf esitligi: `/Media/...` de ayni yoldur; onek karsilastirmasi
     * kucuk harfe indirgenerek yapilir (yolun KENDISI degistirilmez).
     */
    protected function isLowercaseExemptPath(string $path): bool
    {
        $karsilastirilan = mb_strtolower($path, 'UTF-8');

        foreach (self::LOWERCASE_EXEMPT_PREFIXES as $onek) {
            if ($karsilastirilan === $onek || str_starts_with($karsilastirilan, $onek . '/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Rule: Redirect old WordPress URLs to new standardized structures globally 🔄
     */
    protected function redirectOldUrls(): void
    {
        $pathLower = strtolower($this->path);
        $cleanPath = ltrim($pathLower, '/');

        if (empty($cleanPath)) {
            return;
        }

        // 0. RSS Feed Redirects to /feed
        if ($cleanPath === 'rss' || $cleanPath === 'rss.xml') {
            $this->path = '/feed';
            return;
        }

        // 0.1 FAQ Legacy URL Redirect (/sikca-sorulan-sorular -> /sik-sorulan-sorular) 🚀
        if ($cleanPath === 'sikca-sorulan-sorular') {
            $this->path = '/sik-sorulan-sorular';
            return;
        }

        // 0.5. Subfolder robots.txt redirects to root /robots.txt
        if (str_ends_with($cleanPath, '/robots.txt')) {
            $this->path = '/robots.txt';
            return;
        }

        // 0.6. WordPress Legacy Date Post Pattern (e.g., /2026/04/28/post-slug) 🧭⚡
        if (preg_match('#^\d{4}/\d{2}/\d{2}/(.+)$#', $cleanPath, $matches)) {
            $slug = $matches[1];
            $targetUrl = $this->resolveLegacyPostRedirect($slug, $this->projectKey, '/blog');
            $this->path = !empty($targetUrl) ? $targetUrl : '/blog';
            return;
        }

        // 0.7. WordPress Legacy Category Pattern (e.g., /category/kategori-adi) 📁
        if (preg_match('#^category/(.+)$#', $cleanPath, $matches)) {
            $catSlug = $matches[1];
            $targetUrl = $this->resolveLegacyCategoryRedirect($catSlug, $this->projectKey, '/blog');
            $this->path = !empty($targetUrl) ? $targetUrl : '/blog';
            return;
        }

        // 0.8. WordPress Legacy Tag Pattern (e.g., /tag/etiket-adi) 🏷️
        if (preg_match('#^tag/(.+)$#', $cleanPath, $matches)) {
            $this->path = $this->resolveLegacyTagRedirect($matches[1], '/blog');
            return;
        }

        if (str_contains($cleanPath, '/')) {
            return;
        }

        // 1. Old Sitemap Redirects (e.g., /post-sitemap7.xml)
        if (preg_match('/^post-sitemap\d+\.xml$/', $cleanPath)) {
            $this->path = '/sitemap.xml';
            return;
        }

        // 2. Old Corporate & Legal Page Redirects (Database verification fallback)
        $targetSlug = RouteBlueprint::LEGAL_ALIASES[$cleanPath] ?? $cleanPath;

        $baseProject = $this->service('base.project');
        if ($baseProject && method_exists($baseProject, 'getProjectPayload')) {
            $pages = $baseProject->getProjectPayload(['slug' => $targetSlug, 'is_active' => 1])['pages'] ?? [];
            if (!empty($pages)) {
                $this->path = '/sayfa/' . $targetSlug;
                return;
            }
        }
    }

    /**
     * Rule: Redirect /home, /main, and honeypots to root / 🏠
     */
    protected function redirectHome(): void
    {
        $pathLower = strtolower($this->path);

        // Redirect year-only archive URLs (e.g. /2022, /2023)
        $cleanPath = trim($pathLower, '/');
        if (preg_match('/^\d{4}$/', $cleanPath)) {
            $this->path = '/';
            return;
        }

        // Exact path matches
        if (in_array($pathLower, RouteBlueprint::HONEYPOT_PATHS, true) && !$this->isApplicationRoute($pathLower)) {
            $this->path = '/';
            return;
        }

        // Substring pattern matches for attack scanner honeypots
        foreach (RouteBlueprint::HONEYPOT_KEYWORDS as $keyword) {
            if (str_contains($pathLower, $keyword)) {
                $this->path = '/';
                return;
            }
        }

        // Redirect legacy font/webfont requests (excluding framework assets)
        foreach (RouteBlueprint::LEGACY_FONTS_KEYWORDS as $fontKeyword) {
            if (str_contains($pathLower, $fontKeyword) && !str_starts_with($pathLower, '/framework-assets')) {
                $this->path = '/';
                return;
            }
        }

        // Redirect any missing .php, .html, or .htm file requests (scans)
        if (str_ends_with($pathLower, '.php') || str_ends_with($pathLower, '.html') || str_ends_with($pathLower, '.htm')) {
            $this->path = '/';
        }
    }

    /**
     * [R-02] "Bu tuzak yolu aslında bir UYGULAMA sayfası mı?" kontrolü.
     *
     * Yalnız `RouteBlueprint::HONEYPOT_ROUTE_EXEMPT` içindeki önekler için
     * anlamlıdır (`/admin`, `/test`, `/demo`, `/site`, `/ip`). Bu adreslerden
     * biri gerçek bir rotaya eşleşiyorsa tuzak DEĞİLDİR: eski davranış
     * (301 → `/`) uygulanmaz ve istek normal akışta o sayfaya gider.
     *
     * FAIL-CLOSED: rota koleksiyonu boşsa, Matcher/Router yoksa ya da bir
     * istisna olursa `false` döner → eski davranış (301 → `/`) korunur.
     * Yani bu kontrol tuzağı ASLA zayıflatmaz; yalnız gerçek sayfaları
     * kurtarır.
     *
     * KIRMA YAPISI: yalnız TAM eşleşen `HONEYPOT_PATHS` girdileri için
     * çağrılır; `HONEYPOT_KEYWORDS`, eski font ve uzantı kuralları
     * DEĞİŞTİRİLMEMİŞTİR.
     */
    protected function isApplicationRoute(string $pathLower): bool
    {
        if (!in_array($pathLower, RouteBlueprint::HONEYPOT_ROUTE_EXEMPT, true)) {
            return false;
        }

        try {
            if (!class_exists(Router::class) || !class_exists(Matcher::class)) {
                return false;
            }

            $routes = $this->collectHoneypotRoutes();
            if (!is_array($routes) || $routes === []) {
                return false;   // rota yok → eski davranış
            }

            $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
            if ($method === 'HEAD') {
                $method = 'GET';
            }

            $matcher = new Matcher($routes);

            // Kullanıcı yöntemi önce; sonra yaygın yöntemler (sayfa POST ile
            // de açılabilir). Yalnız VAR olan bir eşleşme muafiyet verir.
            foreach (array_unique([$method, 'GET', 'POST']) as $denenecek) {
                if ($matcher->match($pathLower, $denenecek) !== null) {
                    return true;
                }
            }
        } catch (\Throwable) {
            return false;   // ölçüm/arka plan katmanı kararı bozamaz
        }

        return false;
    }

    /**
     * [R-02] Rota koleksiyonunu verir (tek nokta; test alt sınıfı override eder).
     *
     * `Route::run()` içinde `RedirectManager::process()` `Route::dispatch()`
     * DEN önce çalışır; `Routing` aşaması `Route::loadRoutes()`'i çoktan
     * bitirdiği için koleksiyon hazırdır. Erişilemezse boş dizi döner ve
     * çağıran fail-closed olarak eski davranışa düşer.
     *
     * @return array<int,array<string,mixed>>
     */
    protected function collectHoneypotRoutes(): array
    {
        $routes = Router::getInstance()->getRoutes();

        return is_array($routes) ? $routes : [];
    }

    /**
     * Check if the request is secure (HTTPS) 🔒
     */
    protected function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['SERVER_PORT'] == 443)
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }

    /**
     * Rule: Dinamik olarak standart favicon, apple-touch-icon ve og-image yollarını aktif proje dosyalarına yönlendirir 🩹
     */
    protected function selfHealingAssets(): void
    {
        $cleanUri = trim($this->path, '/');
        $projectKey = function_exists('project_key') ? project_key() : 'default';

        if (!$projectKey) {
            return;
        }

        // 1. Favicon & Mobil Kısayol Dosyaları (ico, png, svg, apple-touch-icon, android-chrome)
        $isFavicon = false;

        // [FW-ASSET-KONVANSIYON] Hedef artık SABİT `.png` DEĞİL: projenin
        // GERÇEK favicon dosyası çözülür (`images/favicon-{key}.{svg,png,ico}`).
        // Ölçülen hata: `favicon-<key>.svg` olan projede `/favicon.ico` →
        // 301 → `/images/favicon-<key>.png` → 404 (uçantı yanlış yazılıyordu).
        // Çözümleyici TEK YERDE: `AssetConvention` (SeoResolver ile aynı).
        $faviconRel = AssetConvention::findFavicon($projectKey);
        $faviconTarget = $faviconRel !== null
            ? '/' . $faviconRel
            : '/framework-assets/images/favicon-rbnauth.svg';

        if (in_array($cleanUri, RouteBlueprint::FALLBACK_FAVICONS, true)) {
            $isFavicon = true;
        } else {
            // Apple Touch ve Android Chrome ön ek kontrolleri
            foreach (RouteBlueprint::FALLBACK_MOBILE_PREFIXES as $prefix) {
                if (str_starts_with($cleanUri, $prefix)) {
                    $isFavicon = true;
                    break;
                }
            }
            // Diğer PWA manifest/config dosyaları
            if (!$isFavicon && in_array($cleanUri, RouteBlueprint::FALLBACK_MOBILE_MANIFESTS, true)) {
                $isFavicon = true;
            }
        }

        if ($isFavicon) {
            $this->path = $faviconTarget;
            return;
        }

        // 2. OpenGraph/Sosyal Paylaşım Görselleri
        // [FW-ASSET-KONVANSIYON] Hedef sabit `.png` değil; projenin GERÇEK
        // og dosyası çözülür (`images/og-image-{key}.{png,jpg,webp}`).
        // Proje dosyası yoksa UYDURMA adres üretilmez: yol olduğu gibi bırakılır
        // (düzgün 404) — kural "dosya yoksa meta etiketi yok" ile aynıdır.
        if (in_array($cleanUri, RouteBlueprint::FALLBACK_OG_IMAGES, true)) {
            $ogRel = AssetConvention::findOgImage($projectKey);
            if ($ogRel !== null) {
                $this->path = '/' . $ogRel;
            }
            return;
        }
    }

    /**
     * API, Webhook veya Harici Bot İsteklerinin Yönlendirmeye (301) Uğramasını Engeller 📡🛡️
     */
    protected function shouldBypassRedirect(): bool
    {
        // 1. Standart API & Webhook uç noktaları
        if ($this->hasSegment($this->path, 'api') || $this->hasSegment($this->path, 'webhook')) {
            return true;
        }

        // 2. Bot senkronizasyon ve veri uç noktaları
        if ($this->hasSegment($this->path, 'bot-sync') || $this->hasSegment($this->path, 'bot-data')) {
            return true;
        }

        return false;
    }

    /**
     * [R-18] Yolun segmentlerinden biri TAM OLARAK verilen ad mi?
     *
     * Onceki kod `str_contains($path, '/webhook')` idi: yolun HERHANGI bir
     * yerinde gecmesi yeterliydi. `/blog/yazi-webhook-nasil-kurulur` gibi yasal
     * bir sayfa 301 normalizasyonundan muaf kaliyordu. Daha onemlisi: bu bypass
     * `enforceCanonicalDomain`u da attigi icin kanaldaki host zorlamasi sagte
     * `Host` basligiyla atlanabiliyordu.
     *
     * Segment sonu siniri:
     *   `/api`, `/api/x`, `/telegram/webhook`   -> true
     *   `/apifoo`, `/blog/yazi-webhook-nasil`   -> false
     */
    private function hasSegment(string $path, string $segment): bool
    {
        return in_array($segment, explode('/', trim((string) $path, '/')), true);
    }

    /**
     * [B-5 / FW-BANU-BULGULAR-2] Guvenilir kok COZULEMEDIGINDE 301 YAZMA.
     *
     * OLCUM (E:\tmp\_gecici, `olc_b5.php`; kural dizisi gercekten calistirildi):
     * `project_data('domain')` ve `group_projects()` cozulemediginde beyaz liste
     * `[]` olur ve `guvenliHedef()` her MUTLAK hedefi `/`e dusurur:
     *
     *   /blog/  -> final=http://site.example/blog  -> redirect() -> Location: /
     *   /       -> degisim YOK -> redirect() CAGRILMAZ  (yani sonsuz dongu KURULMAZ)
     *
     * Yani "dongu" degil, **tarayicida KALICI ONBELLEGE alinan 301** riski var:
     * `header("HTTP/1.1 301 Moved Permanently")` tarayicida surekli saklanir; tek
     * bir kesfi/DB hicpi sonrasinda kullanici o URL'i bir daha dogrudan acamaz.
     *
     * Guvenilir kok cozulemedigi icin kural ZATEN UYGULANAMAZ (beyaz liste
     * bosken hangi host'un guvenli oldugu BILINMEZ). Bu yuzden hedefin host'u
     * istegin KENDI host'u ise hicbir `Location` yazmadan istek yola devam eder.
     * KENDI host'u disinda bir hedef uretilmis olsaydi (bu durumda
     * `enforceCanonicalDomain` `$officialDomain` bos oldugu icin erken doner,
     * yani uretilmez) yine de guvenli varsayilana dusulur.
     */
    private function kokCozulemedigindeKendiHostundaMi(string $url): bool
    {
        // Kontrol karakteri / CRLF: hicbir durumda "gecerli" sayilamaz.
        if (preg_match('/[\x00-\x1F\x7F]/', $url) === 1) {
            return false;
        }

        $hedefHost = parse_url(str_replace('\\', '/', $url), PHP_URL_HOST);
        if (!is_string($hedefHost) || $hedefHost === '') {
            return false;
        }
        $hedefHost = rtrim(strtolower($hedefHost), '.');

        $istekHost = $this->normalizeIstekHost(); // trait'te TEK merkez (PreBoot)

        return $hedefHost !== '' && $istekHost !== '' && $hedefHost === $istekHost;
    }

    /**
     * Triggers a 301 redirect and terminates request execution 🛑
     */
    protected function redirect(string $url, int $statusCode = 301): void
    {
        // [B-5] Beyaz liste bos = guvenilir kok bilinmiyor = kural uygulanamaz.
        // Hedef istegin kendi host'undaysa 301 YAZILMAZ (Location yok, exit yok).
        if ($this->beyazListeHostlari() === [] && $this->kokCozulemedigindeKendiHostundaMi($url)) {
            return;
        }

        // [GUVENLIK / FW-110 A-2] `Location` yazmadan ONCE ayni-origin dogrulamasi.
        // Kural listesi ne uretirse uretsin dis hedef (`evil.example`) bu noktada
        // guvenli varsayilana (`/`) duser; hicbir kural bu kapiyi atlayamaz.
        $url = $this->guvenliYonlendirmeHedefi($url);

        if (!headers_sent()) {
            header("HTTP/1.1 " . $statusCode . " Moved Permanently");
            header("Location: " . $url);
        }
        exit();
    }
}

