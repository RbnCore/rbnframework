<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Web;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\Render\ViewEngine;
use Rbn\Framework\Core\Render\Resolvers\ViewResolver;
use Rbn\Framework\Core\Render\Resolvers\LayoutResolver;
use Rbn\Framework\Core\Render\Providers\UI\ViewProvider;
use Rbn\Framework\Core\Render\Resolvers\SeoResolver;
use Rbn\Framework\Core\Render\Resolvers\BreadcrumbResolver;
use Rbn\Framework\Core\System\Discovery\Clusters\Resources\AssetResolver;
use Rbn\Framework\Core\Render\Services\AssetService;
use Rbn\Framework\Core\Support\Exceptions\ViewNotFoundException;

/**
 * BaseRender - Core Base Class for Unified Hub Components 🛡️⚓
 * 
 * RBN 3.5: Powered by BaseComponent DNA.
 * Masterpiece: Integrated Discovery Septet/Decet gateway for Rendering.
 */
abstract class BaseRender extends BaseComponent
{
    /** @var array Standardized Template Context (Base DNA) 🎻⚓ */
    protected array $baseHarmony = [];

    /** @var bool Loop guard to prevent redundant booting 🛡️ */
    private bool $harmonyBooted = false;

    /**
     * Standard Constructor: Dependency Injection via DNA.
     */
    public function __construct(?BaseService $rbn = null)
    {
        // 🎼 RBN 3.5: Minimalist constructor to prevent circular dependencies during boot.
        // We no longer fetch settings or site data here. 🧬🧼
        parent::__construct($rbn);
    }

    /**
     * Harmony: Return the full context with local data for rendering 🎼✨
     * 
     * RBN 3.5 Masterpiece: This acts as the "Lazy Loader" for the render context.
     * It ensures settings are only fetched when a view is actually being prepared.
     */
    public function bootHarmony(): array
    {
        if ($this->harmonyBooted) {
            return $this->baseHarmony;
        }

        // 🎯 RBN 3.5 [RECURSION BRAKE] 🏹
        // Avoid database interaction if the framework is in panic mode or terminal failure.
        if (defined('RBN_PANIC_ACTIVE')) {
            // 🛡️ B-52: sonuç `baseHarmony`'ye de yazılır; döndürülen DEĞER
            // değişmez (yine boş dizi), sadece her çağrıda aynı `defined()`
            // kontrolünü tekrarlamak gerekmez.
            // NOT: `harmonyBooted` BİLİNÇLİ OLARAK set EDİLMİYOR — set edilseydi
            // "panik" ile "başarılı boot" birbirinden ayırt edilemezdi.
            return $this->baseHarmony = [];
        }

        // 🎯 RBN 3.5: [AUTONOMOUS SERVICE DISCOVERY] 🚀
        // If the service property is null, attempt to resolve it via the current module context.
        if ($this->service === null && $this->module !== null) {
            $this->service = $this->service($this->module);
        }

        // 🎯 RBN 3.5: [UNBREAKABLE SERVICE SHIELD] 🛡️
        // Ensure $service is NEVER null in the view context. 
        $viewService = $this->service ?? $this->rbn;

        /** @var \Rbn\Framework\Core\Render\Services\SeoService $seo */
        $seo = $this->service('seo');

        // 🎼 Global Context Fallback: Inject 'user' ONLY if active session exists 🛡️
        $userSessionData = [];
        $auth = $this->service('auth');
        if ($auth && $auth->check()) {
            $userSessionData['user'] = $auth->user();
        } elseif ($this->session()->get('is_logged_in')) {
            $session = $this->session();
            $userSessionData['user'] = [
                'id' => $session->get('user_id'),
                'role' => $session->get('user_role', 'user'),
                'name' => $session->get('user_name', 'Kullanıcı'),
                'username' => $session->get('user_username', 'user'),
                'email' => $session->get('user_email', ''),
                'profile_image' => $session->get('user_profile_image'),
                'is_master' => (bool) $session->get('is_master_developer', false)
            ];
        }

        // 🎹 2. Ultimate Harmony DNA Integration 🚀🔋
        $this->baseHarmony = array_merge($this->bootConcernsContext(), $userSessionData, [
            'Route' => $this->Route,
            'request' => $this->request,
            'response' => $this->response,
            'discover' => $this->discover,
            'rbn' => $this->rbn,

            // 🎼 The Sovereign Service object for views (Safe Access Proxy)
            'sovereign' => $viewService,

            // 🏛️ Layout Resolution (Exposed for direct page usage)
            'layout' => $this->layoutResolver(),
            'now' => now(),

            // 🎼 SMART CLOSURES
            'helper' => fn(string $name) => $this->helper($name),
            'service' => fn(string $name) => $this->service($name)
        ]);

        $this->harmonyBooted = true;
        return $this->baseHarmony;
    }

    /**
     * Standardized Exception: Handle missing views or templates.
     */
    protected function handleMissing(string $path): void
    {
        throw new ViewNotFoundException($path);
    }

    /**
     * Context Preparation (Default Implementation) 🎡⚓
     */
    public function prepare(string $type, ?string $view, array $data): array
    {
        return $data;
    }

    /**
     * Unified access to the core View Engine cluster. 🪐🚀
     */
    public function viewEngine(): ViewEngine
    {
        return $this->cluster('view_engine');
    }

    /**
     * Unified access to the View Path Resolver cluster. 卫星👁️
     */
    protected function viewResolver(): ViewResolver
    {
        return $this->resolver('view');
    }

    /**
     * Unified access to the Layout & Section Resolver cluster. 🏛️🛰️⚓
     */
    protected function layoutResolver(): LayoutResolver
    {
        return $this->resolver('layout');
    }

    /**
     * Unified access to the Asset Discovery Resolver cluster. 🎢🛰️⚓
     */
    protected function assetResolver(): AssetResolver
    {
        return $this->cluster('asset');
    }

    /**
     * Unified access to the Sovereign Asset Service orchestrator. 🎼🛰️⚓
     */
    protected function assetService(): AssetService
    {
        return $this->service('asset');
    }

    /**
     * Internal access to ViewProvider via Discovery. 🎭🛰️⚓
     */
    protected function viewProvider(): ViewProvider
    {
        return $this->provider('view');
    }

    /**
     * Unified access to the Sovereign SEO Resolver cluster. 🕵️‍♂️🛰️⚓
     */
    protected function seoResolver(): SeoResolver
    {
        return $this->resolver('seo');
    }

    /**
     * Unified access to the Sovereign Breadcrumb Resolver cluster. 🗺️🛰️⚓
     */
    protected function breadcrumbResolver(): BreadcrumbResolver
    {
        return $this->resolver('breadcrumb');
    }

    /**
     * RBN 3.5: Masterpiece Fragment Guard 🛡️🛰️⚓
     * Check if the data contains a fragment request and handle it.
     * 
     * @param string $viewPath Physical path for the view
     * @param array $data Data context
     * @return string|null The section content or null if no fragment requested
     */
    protected function resolveFragment(string $viewPath, array $data): ?string
    {
        if (isset($data['__fragment'])) {
            $this->viewEngine()->render($viewPath, $data);
            ob_get_clean(); // Clears current output buffer started by the provider
            return $this->layoutResolver()->getSection((string) $data['__fragment']);
        }
        return null;
    }
}
