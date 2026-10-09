<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Builders;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\Render\Configs\AssetConfig;
use Rbn\Framework\Core\Support\Definitions\Render\AssetBundles;

/**
 * AssetBuilder - State accumulator, dependency resolver, and assembly builder for Assets 🦾🛰️⚓
 * Part of RBN Framework.
 */
class AssetBuilder extends BaseComponent
{
    /** @var array Accumulated assets */
    protected array $styles = [];
    protected array $scripts = [];
    protected string $currentContext = 'frontend';

    /** @var int Depth protection for recursive collection */
    private static int $depth = 0;

    /**
     * Reset the builder state.
     */
    public function reset(): self
    {
        $this->styles = [];
        $this->scripts = [];
        $this->currentContext = 'frontend';
        return $this;
    }

    /**
     * Set/Add Stylesheet to queue. `$attrs` `<link>`'e eklenir (ör. SRI `integrity` + `crossorigin`).
     */
    public function addStyle(string $path, int $priority = 10, array $attrs = []): self
    {
        if (!isset($this->styles[$path])) {
            $this->styles[$path] = ['path' => $path, 'priority' => $priority, 'attributes' => $attrs];
        }
        return $this;
    }

    /**
     * Set/Add Script to queue.
     */
    public function addScript(string $path, array $attrs = [], bool $inHead = false, int $priority = 10): self
    {
        foreach ($this->scripts as $script) {
            if ($script['path'] === $path) {
                return $this;
            }
        }

        $this->scripts[] = [
            'path' => $path,
            'attributes' => $attrs,
            'renderInHead' => $inHead,
            'priority' => $priority
        ];
        return $this;
    }

    /**
     * Add virtual font asset to queue.
     */
    public function addFont(string $name, int $priority = 15): self
    {
        $this->addStyle('@font/' . $name, $priority);
        return $this;
    }

    /**
     * Gathers, compiles and structures the assets for a given bundle/source.
     */
    public function prepare(string $appContext, $source = null): self
    {
        $this->currentContext = $appContext;
        $source = $source ?? $appContext;
        $payload = ['styles' => [], 'scripts' => []];
        self::$depth = 0;

        // 1. Gather Core Stacks
        $this->injectCoreStack($payload, $appContext);

        // 2. Gather Custom Sources
        $this->collect($payload, $source, $appContext);

        // 🚀 Eğer frontend sayfasında Controller'dan özel bir font istenmişse, otomatik eklenen varsayılan 'Inter' fontunu temizle
        if ($appContext === 'frontend') {
            $hasCustomFont = false;
            foreach ($payload['styles'] as $item) {
                $path = is_array($item) ? ($item['path'] ?? '') : $item;
                if (str_starts_with($path, '@font/') && strtolower($path) !== '@font/inter') {
                    $hasCustomFont = true;
                    break;
                }
            }

            // Özel bir font varsa ve kullanıcı açıkça '@font/Inter' istemediyse, core stack'ten gelen varsayılan Inter'i temizle
            if ($hasCustomFont) {
                $explicitInter = is_array($source) && in_array('@font/Inter', $source, true);
                if (!$explicitInter) {
                    $payload['styles'] = array_values(array_filter($payload['styles'], function ($item) {
                        $path = is_array($item) ? ($item['path'] ?? '') : $item;
                        return strtolower($path) !== '@font/inter';
                    }));
                }
            }
        }

        // 3. Compile and queue Styles
        foreach ($payload['styles'] as $item) {
            $compiled = $this->compile($item, $appContext);
            if ($compiled) {
                $this->addStyle($compiled['path'], $compiled['priority'] ?? 10, $compiled['attributes']);
            }
        }

        // 4. Compile and queue Scripts
        foreach ($payload['scripts'] as $item) {
            $compiled = $this->compile($item, $appContext);
            if ($compiled) {
                $this->addScript(
                    $compiled['path'],
                    $compiled['attributes'],
                    $compiled['renderInHead'],
                    $compiled['priority'] ?? 10
                );
            }
        }

        return $this;
    }

    /**
     * Build the final structured payload for header/footer rendering.
     */
    public function build(): array
    {
        // 🔤 RBN Framework Graceful Font Fallback: Sadece Frontend'de ve hiç font istenmediyse varsayılan 'Inter' ekle
        $hasAnyFont = false;
        foreach ($this->styles as $styleData) {
            $path = $styleData['path'] ?? '';
            if (str_contains($path, '/fonts/')) {
                $hasAnyFont = true;
                break;
            }
        }

        if (!$hasAnyFont && $this->currentContext === 'frontend') {
            $defaultCompiled = $this->compile('@font/Inter', 'frontend');
            if ($defaultCompiled) {
                $this->styles['@font/Inter'] = [
                    'path' => $defaultCompiled['path'],
                    'priority' => 5 // Fontlar en üstte yüklensin
                ];
            }
        }

        $sortFn = function ($a, $b) {
            // 1. Proje dosyaları (@project) daima en son yüklenir (tüm framework ve CDN'leri ezebilmesi için)
            $isAProject = str_starts_with($a['path'], '/project-assets/');
            $isBProject = str_starts_with($b['path'], '/project-assets/');

            if ($isAProject && !$isBProject) {
                return 1;
            }
            if (!$isAProject && $isBProject) {
                return -1;
            }

            // 2. Çekirdek CSS Motoru (Bootstrap veya rbn-master.css) DAİMA EN ÜSTTE / İLK sırada yüklenir 🏛️
            $isACoreEngine = str_contains($a['path'], 'bootstrap') || str_contains($a['path'], 'rbn-master.css') || str_contains($a['path'], 'rbn-core.css');
            $isBCoreEngine = str_contains($b['path'], 'bootstrap') || str_contains($b['path'], 'rbn-master.css') || str_contains($b['path'], 'rbn-core.css');

            if ($isACoreEngine && !$isBCoreEngine) {
                return -1;
            }
            if (!$isACoreEngine && $isBCoreEngine) {
                return 1;
            }

            if ($a['priority'] !== $b['priority']) {
                return $a['priority'] <=> $b['priority'];
            }

            return 0;
        };

        // Sort styles and scripts based on context hierarchy first, then priority
        uasort($this->styles, $sortFn);
        uasort($this->scripts, $sortFn);

        return [
            'styles' => array_values($this->styles),
            'scripts' => array_values($this->scripts)
        ];
    }

    /**
     * Recursive collection logic.
     */
    protected function collect(array &$payload, $source, string $appContext): void
    {
        self::$depth++;
        if (self::$depth > 8) {
            self::$depth--;
            return;
        }

        $bundles = AssetBundles::BUNDLES;

        if (is_string($source) && isset($bundles[$source])) {
            $source = $bundles[$source];
        }

        if (is_array($source)) {
            foreach ($source as $key => $val) {
                if ($key === 'styles') {
                    $payload['styles'] = array_merge($payload['styles'], (array) $val);
                } elseif ($key === 'scripts') {
                    $payload['scripts'] = array_merge($payload['scripts'], (array) $val);
                } else {
                    $this->collect($payload, $val, $appContext);
                }
            }
        } elseif (is_string($source) && !empty($source)) {
            if (str_ends_with(strtolower($source), '.css') || str_starts_with($source, '@font/')) {
                $payload['styles'][] = $source;
            } else {
                $payload['scripts'][] = $source;
            }
        }

        self::$depth--;
    }

    /**
     * Inject Core Stacks.
     */
    protected function injectCoreStack(array &$payload, string $appContext): void
    {
        $map = AssetBundles::STACK_MAP;

        // 🧠 Projeye özel CSS motoru tercihi: 'bootstrap' (varsayılan) veya 'rbn'
        $projectKey = (string) ($this->resolveProjectData('project_key') ?? '');
        $cssEngine = $this->getRouteConfig($projectKey, 'css_engine');
        $isRbnEngine = (strtolower((string) $cssEngine) === 'rbn');

        $isAuthContext = ($appContext === 'auth');
        $isPanelContext = ($appContext === 'panel');

        foreach ($map['universal'] ?? [] as $bundleName) {
            // Eğer auth/panel sayfasıysa VEYA proje 'rbn' motorunu seçmişse; bootstrap, bootstrap_icons ve font_awesome paketlerini ASLA yükleme (0 KB bypass) 🛡️
            if (($isAuthContext || $isPanelContext || $isRbnEngine) && in_array($bundleName, ['bootstrap', 'bootstrap_icons', 'font_awesome'], true)) {
                continue;
            }
            $this->collect($payload, $bundleName, $appContext);
        }

        // 🚀 Eğer auth/panel sayfasıysa VEYA proje 'rbn' motorunu seçmişse, RBN Master motorunu otomatik yükle
        if ($isAuthContext || ($isRbnEngine && $appContext === 'frontend')) {
            $this->collect($payload, 'rbn_master', $appContext);
        }

        if ($appContext !== 'universal' && isset($map[$appContext])) {
            foreach ($map[$appContext] as $bundleName) {
                $this->collect($payload, $bundleName, $appContext);
            }
        }

        // 🛡️ [RBN Framework] 'auth', 'panel' ve 'rbn' motorunda common.css ve rbnAlert.css zaten rbn-master.css içindedir; mükerrerliği önle
        if (($isAuthContext || $isPanelContext || $isRbnEngine) && isset($payload['styles'])) {
            $payload['styles'] = array_values(array_filter($payload['styles'], function ($style) {
                $path = is_array($style) ? ($style['path'] ?? '') : $style;
                return !str_contains($path, 'common.css') && !str_contains($path, 'rbnAlert.css');
            }));
        }
    }


    /**
     * Compile logic (previously in AssetCompiler).
     */
    public function compile($item, string $appContext = 'frontend'): ?array
    {
        $path = is_array($item) ? ($item['path'] ?? '') : $item;

        [$cleanPath, $sourceHint] = $this->resolveToken($path);

        $detected = ($sourceHint === 'font') ? null : \Rbn\Framework\Core\System\Discovery\Engine\DiscoveryEngine::instance()->assets()->resolve($cleanPath, $sourceHint);

        if (!$detected && !str_contains($cleanPath, 'http') && $sourceHint !== 'font') {
            return null;
        }

        $version = AssetConfig::VERSION;
        if ($detected && file_exists($detected->path)) {
            // [FW-H51] CSS ise import zincirinin en yeni mtime'ı: alt dosya değişince URL değişir.
            $version = (string) (str_ends_with(strtolower($detected->path), '.css')
                ? \Rbn\Framework\Core\Render\Handlers\CssImportVersioner::chainMtime($detected->path)
                : filemtime($detected->path));
        }

        $webUrl = $this->generateWebUrl($cleanPath, $sourceHint, $version);

        return [
            'path' => $webUrl,
            'type' => $this->detectType($cleanPath),
            'renderInHead' => is_array($item) ? ($item['renderInHead'] ?? false) : false,
            'attributes' => is_array($item) ? ($item['attributes'] ?? []) : [],
            'priority' => is_array($item) ? ($item['priority'] ?? 10) : 10,
            'metadata' => $detected
        ];
    }

    protected function generateWebUrl(string $path, ?string $sourceHint, string $version): string
    {
        if ($sourceHint === 'font') {
            return url('/framework-assets/fonts/' . strtolower(str_replace(' ', '-', $path)));
        }

        if (str_contains($path, 'http')) {
            return $path;
        }

        $setup = AssetConfig::PROXY_SETUP;
        $source = $sourceHint ?? 'project';
        $proxy = $setup[$source]['path'] ?? 'assets';

        $cleanPath = str_replace('\\', '/', $path);
        $parts = explode('/', trim($cleanPath, '/'));

        if (count($parts) > 1 && $source === 'framework') {
            $cluster = strtolower($parts[0]);
            if ($cluster === 'images') {
                $finalPath = strtolower($cleanPath);
            } else {
                $fileName = strtolower(end($parts));
                $finalPath = "{$cluster}/{$fileName}";
            }
        } else {
            $finalPath = strtolower($cleanPath);
        }

        return url('/' . $proxy . '/' . ltrim($finalPath, '/') . "=v{$version}");
    }

    protected function resolveToken(string $path): array
    {
        $setup = AssetConfig::PROXY_SETUP;
        $hint = null;

        foreach (['project', 'framework'] as $source) {
            if (isset($setup[$source]['token']) && str_starts_with($path, $setup[$source]['token'])) {
                $hint = $source;
                $path = str_replace($setup[$source]['token'], '', $path);
                break;
            }
        }

        if (!$hint && str_starts_with($path, '@font/')) {
            $hint = 'font';
            $path = str_replace('@font/', '', $path);
        }

        return [$path, $hint];
    }



    protected function detectType(string $path): string
    {
        $ext = strtolower(pathinfo(parse_url($path, PHP_URL_PATH), PATHINFO_EXTENSION));
        return AssetConfig::EXTENSION_MAP[$ext] ?? 'js';
    }
}
