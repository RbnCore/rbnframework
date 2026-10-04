<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render;

use Rbn\Framework\Core\Base\Web\BaseRender;
use Rbn\Framework\Core\Render\Configs\AssetConfig;

/**
 * View - The Unified Render Orchestrator 📽️🛰️⚓
 * 
 * RBN 3.5 Masterpiece: Unified Architecture.
 * Now a core occupant of the Resolvers/View cluster.
 */
class View extends BaseRender
{
    /** @var string View path/name */
    protected string $view;

    /** @var array Local view data */
    protected array $data = [];

    /** @var string Theme/Type (frontend/panel) */
    protected string $type;

    /** @var string|null Specific partial fragment */
    protected ?string $fragment = null;

    /** @var array Imported partials/templates 🛰️⚓ */
    protected array $imports = [];

    /** @var array|null Pending MetaSEO parameters */
    protected ?array $pendingMetaseo = null;

    /** @var array Pending Schema definitions */
    protected array $pendingSchemas = [];

    /** @var string|null Cached compiled HTML output */
    protected ?string $renderedHtml = null;

    /**
     * Create a new View instance (Internal DNA Build) 🎬⚓
     */
    protected function __construct(string $view, array $data = [], string $type = 'frontend')
    {
        $this->view = $view;
        $this->data = $data;
        $this->type = $type;
        $this->module = $type; // 🎼 RBN 3.5: Module context synchronization

        // 🎻 RBN 3.5: [AUTONOMOUS AJAX DETECTION] 🛰️⚓
        // Shift to AjaxProvider if the data explicitly requests it.
        if (isset($this->data['ajax']) && $this->data['ajax'] === true) {
            $this->type = 'ajax';
            $this->module = 'ajax';
        }

        // 🧬 RBN 3.5: Initialize DNA via BaseRender (Discovery, Request, Context)
        parent::__construct();
    }

    /**
     * Import a partial/template for autonomous rendering 🛰️⚓
     */
    public function import($path): self
    {
        if (is_array($path)) {
            $this->imports = array_merge($this->imports, $path);
        } else {
            $this->imports[] = $path;
        }
        return $this;
    }

    /**
     * Add a dynamic schema to the page context fluently. 🛰️⚓
     */
    public function schema(string $type, array $data = []): self
    {
        $this->pendingSchemas[] = ['type' => $type, 'data' => $data];
        return $this;
    }

    /**
     * Set the page SEO overrides fluently. 🛰️⚓
     */
    public function metaseo(string $title, string $description = '', string $keywords = ''): self
    {
        $this->pendingMetaseo = [
            'title'       => $title,
            'description' => $description,
            'keywords'    => $keywords
        ];
        return $this;
    }

    /**
     * Static Entry Point: Start a fluent render process 🏹
     * 
     * @param string $view Logical view name (e.g. 'auth/login')
     * @param array $data Additional context data
     * @param string $type The context type (e.g. 'frontend', 'panel')
     * @return static
     */
    public static function render(string $view, array $data = [], string $type = 'frontend'): self
    {
        return new static($view, $data, $type);
    }

    /**
     * Add data to the view context (Fluent) 📥
     */
    public function with($key, $value = null): self
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }
        return $this;
    }

    /**
     * Specify a fragment (section) to return instead of full view 🎭
     */
    public function fragment(string $name): self
    {
        $this->fragment = $name;
        return $this;
    }

    /**
     * Harmony: Return the full context with local data for rendering 🎼✨
     */
    public function viewHarmony(): array
    {
        // 🎼 RBN 3.5: [DNA WAKE UP] 🧬⚓
        // Ensure core harmony variables (Route, site, helpers) are booted.
        return array_merge($this->bootHarmony(), $this->data);
    }

    /**
     * Execute final render and return HTML string 🚀🛰️
     */
    public function result(): string
    {
        if ($this->renderedHtml !== null) {
            return $this->renderedHtml;
        }

        // 1. Process MetaSEO FIRST so title & description are registered 🛡️
        if ($this->pendingMetaseo !== null) {
            $seoService = $this->service('seo');
            if ($seoService) {
                $seoService->prepare($this->type, $this->pendingMetaseo);
            }
        }

        // 2. Process Schemas SECOND so schemaProvider reads updated title & description 🧬
        if (!empty($this->pendingSchemas)) {
            $schemaProvider = $this->provider('schema');
            if ($schemaProvider) {
                foreach ($this->pendingSchemas as $sItem) {
                    $schemaProvider->build($sItem['type'], $sItem['data']);
                }
            }
        }

        $renderService = $this->service('render');

        if ($this->fragment) {
            $this->data['__fragment'] = $this->fragment;
        }

        if (!empty($this->imports)) {
            $this->data['__imports'] = $this->imports;
        }

        $html = (string) $renderService->render($this->type, [
            'view' => $this->view,
            'data' => $this->data // 🎼 Veri doğrudan aktarılır, harmony birleştirmesi tek noktada (ViewEngine/Provider) yapılır
        ]);

        // 🎭 RBN 3.5: Parse placeholder {year} globally in the final HTML
        $html = str_replace('{year}', date('Y'), $html);

        // 🎨 RBN 3.5: Autonomous Global Media Proxy Configuration 🖼️⚓
        $setup = AssetConfig::PROXY_SETUP;
        $source = in_array($this->type, ['framework', 'core'], true) ? 'framework' : 'project';
        $proxyPath = $setup[$source]['path'] ?? 'project-assets';

        $prPrefix = '/' . ($setup['project']['path'] ?? 'project-assets') . '/';
        $fwPrefix = '/' . ($setup['framework']['path'] ?? 'framework-assets') . '/';

        // 🎼 RBN 3.5: Sovereign HTML Normalization Process (Core DNA SSoT) 🛰️⚓
        return $this->renderedHtml = $this->normalizeHtmlUrls($html, null, [
            'pr_prefix' => $prPrefix,
            'fw_prefix' => $fwPrefix,
            'proxy_path' => $proxyPath,
            'media_prefix' => '/media/'
        ]);
    }

    /**
     * Get the current view path
     */
    public function getViewPath(): string
    {
        return $this->view;
    }

    /**
     * Fluent output dispatch
     */
    public function __toString(): string
    {
        try {
            return $this->result();
        } catch (\Throwable $e) {
            // 🛡️ RBN 3.5: Kill any hanging buffers to expose the error
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            // R-11: mutlak yol + istisna mesajı yalnız yerel geliştirmede görünür; prod'da genel metin.
            if (defined('RBN_DEV') && RBN_DEV === true) {
                return "[Render Error]: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine();
            }
            return '[Render Error]';
        }
    }
}
