<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\System\Paths\Paths;
use Rbn\Framework\Core\Base\Web\BaseRender;
use Rbn\Framework\Core\System\Discovery\Engine\DiscoveryEngine;
use Rbn\Framework\Core\Render\Handlers\TemplateExpressionGuard;

/**
 * ViewEngine - RBN Framework Modern Template Engine 🎨
 * 
 * RBN 3.5: Powered by BaseComponent DNA via BaseRender.
 * Focused purely on compilation and parsing. Layout logic is delegated to LayoutResolver.
 */
class ViewEngine extends BaseRender
{
    private string $cachePath;

    /**
     * [R-03] Derlenmekte olan view yolu. Hata mesajinda YALNIZCA dosya adi
     * gosterilir: tam yol HTTP govdesine (RbnShield hata sayfasi) duser ve
     * sunucudaki dizin yapisini sizdirirdi.
     *
     * @var string|null
     */
    protected ?string $derlenenViewYolu = null;

    /** @var array|null Static directives pattern cache 🧠⚡ */
    private static ?array $cachedDirectives = null;

    /** @var array<string, string> View path resolution cache 🧠⚡ */
    private static array $resolvedViewPaths = [];

    /**
     * ViewEngine Constructor 🧬⚓
     */
    public function __construct(?BaseService $rbn = null)
    {
        // 🧬 Root DNA'yı ayağa kaldır.
        parent::__construct($rbn);

        // 🎼 RBN 3.5: [AUTONOMOUS CACHE RESOLUTION] 🛰️⚓
        $this->cachePath = Paths::project()->storage('framework/views');
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    /**
     * Render the given view path with data.
     */
    public function render(string $viewPath, array $data = []): void
    {
        // [RBN 3.5] Zero-Scan Discovery-First Resolution 🕵️‍♂️⚓
        if (isset(self::$resolvedViewPaths[$viewPath])) {
            $viewPath = self::$resolvedViewPaths[$viewPath];
        } else {
            $originalPath = $viewPath;
            if (!file_exists($viewPath)) {
                $detected = DiscoveryEngine::instance()->views()->resolve($viewPath);
                if ($detected) {
                    $viewPath = is_string($detected) ? $detected : (method_exists($detected, 'getPath') ? $detected->getPath() : (string) $detected);
                }
            }
            self::$resolvedViewPaths[$originalPath] = $viewPath;
        }

        $compiledPath = $this->compile($viewPath);

        // 🎨 RBN 3.5: Masterpiece Harmony Hub (Lazy-Resolved in BaseRender) 🎻⚓
        $data = array_merge($this->bootHarmony(), $data);

        $layoutResolver = $this->layoutResolver();
        $this->includeCompiled($compiledPath, $data);

        // 🏛️ Layout Resolution (Delegated to LayoutResolver) 🧬🛰️⚓
        $layout = $layoutResolver->getExtends();
        if ($layout) {
            $layoutResolver->resetExtends();

            // [DISCOVERY] Resolve Layout Blueprint 🛰️🏛️
            if (isset(self::$resolvedViewPaths[$layout])) {
                $layout = self::$resolvedViewPaths[$layout];
            } else {
                $origLayout = $layout;
                if (!file_exists($layout)) {
                    $detected = DiscoveryEngine::instance()->views()->resolve($layout);
                    if ($detected) {
                        $layout = is_string($detected) ? $detected : (method_exists($detected, 'getPath') ? $detected->getPath() : (string) $detected);
                    } else {
                        $layout = Paths::project()->root("views/{$layout}");
                    }
                }
                self::$resolvedViewPaths[$origLayout] = $layout;
            }

            $this->render($layout, $data);
        }
    }

    /**
     * R-09: Sablon verisini YALNIZ bu izole kapsamda acar ve derlenmis dosyayi dahil eder.
     * Motorun yerel degiskenleri (derlenmis yol, layout cozucu...) bu kapsama girmez; veri
     * anahtarlari yalniz ayrilmis adlarla (`__rbn*`, `this`, `GLOBALS`) cakisabilir, onlar
     * atlanir ve sistem gunlugune yazilir. `$data` eski davranisla uyumlu kalir
     * (veride `data` anahtari yoksa tum dizi).
     */
    private function includeCompiled(string $__rbnFile, array $__rbnData): void
    {
        foreach (['__rbnFile', '__rbnData', 'this', 'GLOBALS'] as $reserved) {
            if (array_key_exists($reserved, $__rbnData)) {
                @error_log('[ViewEngine] sablon verisi ayrilmis adi eziyordu, yoksayildi: ' . $reserved);
                unset($__rbnData[$reserved]);
            }
        }
        $data = $__rbnData;
        extract($__rbnData);
        require $__rbnFile;
    }

    /**
     * Compile a template file to PHP
     */
    public function compile(string $viewPath): string
    {
        // [A0-9 / R-02] Derlenecek view HER ZAMAN proje veya framework kokunde olmali.
        // `@import('../../../../evil.php')` gibi bir arguman once `path()` tarafindan
        // reddedilir; buradaki kontrol ikinci savunma hattidir (path() bypass edilse bile).
        $viewPath = self::guardRoots($viewPath, self::compileRoots(), 'compile()');

        if (!file_exists($viewPath)) {
            $this->handleMissing($viewPath);
        }

        $projectKey = $this->projectKey ?: project_key() ?: 'default';
        $cacheFile = $this->cachePath . '/' . $projectKey . '_' . md5($viewPath) . '.php';

        if (file_exists($cacheFile) && filemtime($cacheFile) >= filemtime($viewPath)) {
            return $cacheFile;
        }

        $content = file_get_contents($viewPath);
        $this->derlenenViewYolu = $viewPath;
        try {
            $compiled = $this->parse($content);
        } finally {
            $this->derlenenViewYolu = null;
        }

        file_put_contents($cacheFile, $compiled);
        return $cacheFile;
    }

    /**
     * Core Parsing Logic
     *
     * [R-03] Sablon ifadeleri ARTIK ham PHP olarak gomulmez: her ifade
     * once `TemplateExpressionGuard` tarafindan denetlenir. Yasakli yapi
     * (backtick, eval, shell, dosya yazma, include/require, `new`, `?>`
     * kirilasi, ...) iceren ifade DERLEME HATASI firlatir (fail-closed).
     *
     * Sifreleme ZATEN vardi; R-03'un kattigi katman ifadenin kendisini
     * guvenli kilar. Korpustaki ~1500 benzersiz meşru ifadenin tamami bu
     * kapidan gecer (0 yanlis-pozitif) — bkz. birim testi.
     */
    protected function parse(string $content): string
    {
        $content = preg_replace('/{{--(.+?)--}}/s', '', $content);
        $content = $this->parseDirectives($content);

        $guard = $this->expressionGuard();
        $dosyaAdi = basename($this->derlenenViewYolu ?? '') ?: 'sablon';

        $content = preg_replace_callback(
            '/{{\s*(.+?)\s*}}/s',
            function (array $m) use ($guard, $dosyaAdi): string {
                $guard->assertSafe($m[1], $dosyaAdi);
                return '<?php echo htmlspecialchars((string)(' . $m[1] . ' ?? ""), ENT_QUOTES, "UTF-8"); ?>';
            },
            $content
        );

        $content = preg_replace_callback(
            '/{!!\s*(.+?)\s*!!}/s',
            function (array $m) use ($guard, $dosyaAdi): string {
                $guard->assertSafe($m[1], $dosyaAdi);
                return '<?php echo ' . $m[1] . ' ?? ""; ?>';
            },
            $content
        );

        return $content;
    }

    /**
     * [R-03] Ifade guvenlik kapisi (surec basina tek nesne).
     */
    protected function expressionGuard(): TemplateExpressionGuard
    {
        static $guard = null;
        if (!$guard instanceof TemplateExpressionGuard) {
            $guard = new TemplateExpressionGuard();
        }
        return $guard;
    }


    protected function parseDirectives(string $content): string
    {
        if (self::$cachedDirectives === null) {
            $directives = [
                // Structural
                '/@if\s*(\((?:[^()]+|(?1))*\))/' => '<?php if $1: ?>',
                '/@elseif\s*(\((?:[^()]+|(?1))*\))/' => '<?php elseif $1: ?>',
                '/@else/' => '<?php else: ?>',
                '/@endif/' => '<?php endif; ?>',

                // Loops
                '/@foreach\s*(\((?:[^()]+|(?1))*\))/' => '<?php foreach $1: ?>',
                '/@endforeach/' => '<?php endforeach; ?>',
                '/@for\s*(\((?:[^()]+|(?1))*\))/' => '<?php for $1: ?>',
                '/@endfor/' => '<?php endfor; ?>',

                // 🏛️ Layout Directives (Proxied to LayoutResolver) 🧬🛰️⚓
                '/@extends\s*\(\s*\'(.+?)\'\s*\)/' => '<?php $layout->setExtends(\'$1\'); ?>',
                '/@section\s*\(\s*\'(.+?)\'\s*\)/' => '<?php $layout->startSection(\'$1\'); ?>',
                '/@endsection/' => '<?php $layout->endSection(); ?>',
                '/@yield\s*\(\s*\'(.+?)\'\s*\)/' => '<?php echo $layout->getSection(\'$1\'); ?>',
            ];

            // 2. Extensible Application Directives (Config Driven)
            $appDirectives = \Rbn\Framework\Core\Render\Configs\ViewConfig::directives();
            self::$cachedDirectives = array_merge($directives, $appDirectives);
        }

        return preg_replace(array_keys(self::$cachedDirectives), array_values(self::$cachedDirectives), $content);
    }


    public function path(string $type, ...$args): string
    {
        $resolved = match (strtolower($type)) {
            'suite' => Paths::framework()->suite(...$args),
            'module', 'modules' => Paths::module(array_shift($args))->root(implode('/', $args)),
            'framework' => Paths::framework()->root(...$args),
            'public', 'web' => Paths::project()->public(...$args),
            'project', 'core', 'root' => Paths::project()->root(...$args),
            default => Paths::project()->root(...$args)
        };

        // [A0-9 / R-02] Cozulen yolun TIPE ait kok dizinde kaldigini dogrula.
        // `..` ile kok disina cikan her yol burada reddedilir (fail-closed).
        $baseArgs = $args;
        $base = match (strtolower($type)) {
            'suite' => Paths::framework()->root(),
            'module', 'modules' => Paths::module(array_shift($baseArgs))->root(),
            'framework' => Paths::framework()->root(),
            'public', 'web' => Paths::project()->public(),
            default => Paths::project()->root(),
        };

        return self::guardRoots($resolved, [$base], "path('{$type}')");
    }

    /**
     * [A0-9 / R-02] @import tek guvenli giris noktasi.
     *
     * View sablonu `@import('project', 'Modules/...')` yazdiginda uretilen PHP
     * `require $this->import($1)` olur; eskiden `compile(path(...))` idi ve
     * iki katman da kok kontrolu yapmadan dosyayi `require` ediyordu.
     *
     * @param string ...$args Ayni imzayla ViewEngine::path() (tip, goreli yol, ...)
     */
    public function import(string ...$args): string
    {
        return $this->compile($this->path(...$args));
    }

    /* ==========================================================================
       [ A0-9 / R-02 - KOK SINIRI (FAIL-CLOSED) ] 🛡️
       ========================================================================== */

    /**
     * Bir view yolunun derlenebilecegi kokler: proje kokui + framework kokui.
     *
     * @return string[]
     */
    private static function compileRoots(): array
    {
        return [Paths::project()->root(), Paths::framework()->root()];
    }

    /**
     * Bir yolun verilen koklerden birinin ICINDE kaldigini dogrular.
     *
     * SIRALAMA:
     *  1) `realpath()` — sembolik bag / `..` / buyuk-kucuk harf cozumlenir.
     *  2) Dosya henuz yoksa (derlenecek view olusabilir) sozluksel normalizasyon.
     *  3) Karsilastirma `str_starts_with($path, $root . '/')` ile KOK-SINIR
     *     kontroludur: `E:/root-iki` ile `E:/root` birbirinin icinde sayilmaz.
     *
     * @param string[] $roots
     * @throws \RuntimeException Yol hicbir kokun icinde degilse.
     */
    private static function guardRoots(string $path, array $roots, string $baglam): string
    {
        $real = realpath($path);
        if ($real !== false) {
            $path = $real;
        }

        // Hata mesajina TAM yol YAZILMAZ: bu mesaj HTTP govdesine (RbnShield
        // hata sayfasi) duser ve sunucudaki dizin yapisini sizdirirdi.
        $ipucu = basename($path) . ' (kok disi)';

        $norm = self::normalizeForCompare($path);
        if ($norm === '') {
            throw new \RuntimeException(
                'RBN Guvenlik: ViewEngine::' . $baglam . ' kok disi yol reddedildi: ' . $ipucu
            );
        }

        foreach ($roots as $root) {
            $rootReal = realpath($root);
            $rootNorm = self::normalizeForCompare($rootReal !== false ? $rootReal : $root);
            if ($rootNorm === '') {
                continue;
            }
            if (self::isInsideRoot($norm, $rootNorm)) {
                return $path;
            }
        }

        throw new \RuntimeException(
            'RBN Guvenlik: ViewEngine::' . $baglam . ' izin verilen kok disina cikiyor (reddedildi): ' . $ipucu
        );
    }

    /**
     * Yolu karsilastirma icin normalize eder: `\` -> `/`, surucu on eki korunur,
     * `.` ve `..` segmentleri cozulur. Kokun USTUNE cikmaya calisan yol `''` doner.
     */
    private static function normalizeForCompare(string $path): string
    {
        $path = str_replace('\\', '/', $path);

        $prefix = '';
        if (preg_match('#^([A-Za-z]:)?/#', $path, $m) === 1) {
            $prefix = ($m[1] ?? '') . '/';
            $path = substr($path, strlen($prefix));
        }

        $out = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                if ($out === []) {
                    return ''; // kokun ustune cikildi -> reddedilecek
                }
                array_pop($out);
                continue;
            }
            $out[] = $segment;
        }

        return $prefix . implode('/', $out);
    }

    /** KOK-SINIR kontrolu: aday yol kokun icinde mi (esit veya `kok/...` mi)? */
    private static function isInsideRoot(string $path, string $root): bool
    {
        // Windows dosya sistemi buyuk/kucuk harf duyarsizdir; POSIX duyarlidir.
        if (PHP_OS_FAMILY === 'Windows') {
            $path = strtolower($path);
            $root = strtolower($root);
        }

        $root = rtrim($root, '/');

        return $path === $root || str_starts_with($path, $root . '/');
    }
}
