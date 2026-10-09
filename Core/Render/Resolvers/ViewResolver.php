<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Resolvers;

use Rbn\Framework\Core\Base\Web\BaseRender;
use Rbn\Framework\Core\System\Paths\Paths;

/**
 * ViewResolver - Intelligent View Path Orchestrator 👁️🛰️⚓
 * 
 * RBN Framework: Powered by Autonomous DNA.
 * Responsible for resolving logical view paths across the entire project/framework hierarchy.
 */
class ViewResolver extends BaseRender
{
    /**
     * Internal: Resolution Cache (Static Request-Level) 📦
     */
    private static array $resolveCache = [];

    /**
     * Resolve a physical view path from logical input. 🛰️⚓⚖️
     * 
     * RBN Framework Logic:
     * 1. Check Project Resources (Resources/Views)
     * 2. Check Contextual Bundle (RbnAdmin for 'panel', RbnAuth for 'auth')
     * 3. Check General Module Discovery
     * 4. Check Framework Fallback (rbnframework/Resources/Views)
     * 
     * Now supports both .php and .rbn.php globally.
     */
    public function resolve(string $view, array $options = []): ?string
    {
        $cacheKey = md5($view . serialize($options));
        if (isset(self::$resolveCache[$cacheKey])) {
            return self::$resolveCache[$cacheKey];
        }

        $viewBase = ltrim($view, '/\\');
        $viewBase = static::toCaseSafePath($viewBase);
        $extensions = ['.php', '.rbn.php'];

        // 🎼 RBN Framework: [RBN Framework CONTEXT DISCOVERY] - Priority: Options > Active Controller 🧠⚓
        $context = $options['context'] ?? ($this->rbn->activeController()->context ?? 'frontend');

        // 🎯 Collect Discovery Targets (Ordered by priority)
        $targets = [];

        // TARGET 0: Module Level (Current Module High Priority) 🎯🛰️
        // 🎼 RBN Framework: [RBN Framework DETECTION] - Priority: Options > Active Controller 🧠⚓
        $module = $options['module'] ?? ($this->rbn->activeController()->module ?? null);
        
        if ($module) {
            try {
                $targets[] = Paths::module((string) $module)->views();
            } catch (\Throwable $e) {
            }
        }

        // TARGET 1: Project Level (Overrides everything else) 🏰
        $targets[] = Paths::project()->views();

        // TARGET 2: RBN Framework Bundle Level (Derived from Active Content) 📦
        $activePanel = $options['panel'] ?? ($this->rbn->activeController()->panel ?? null);
        if ($activePanel) {
            try {
                $targets[] = Paths::module((string) $activePanel, 'suite')->views();
            } catch (\Throwable $e) {}
        }

        // 🛰️ DYNAMIC MODULE DISCOVERY (Context Fallback)
        try {
            $targets[] = Paths::module($context)->views();
        } catch (\Throwable $e) {}

        // TARGET 3: Framework Core (Final Fallback) 🏛️
        $frameworkViews = Paths::framework()->views();
        $targets[] = $frameworkViews;
        $targets[] = $frameworkViews . DIRECTORY_SEPARATOR . 'RbnCommon';

        // 🎼 RBN Framework: Contextual Framework Discovery 🛰️
        if ($activePanel) {
            $targets[] = $frameworkViews . DIRECTORY_SEPARATOR . ucfirst((string) $activePanel);
        }

        if ($context !== 'frontend') {
            $targets[] = $frameworkViews . DIRECTORY_SEPARATOR . ucfirst($context);
        }

        // 🏁 HIERARCHICAL DISCOVERY LOOP 🛰️⚓
        // 🎼 RBN Framework: [RBN Framework PERSISTENCE] - Check Discovery Map first 💾
        $mapper = $this->discover()->getMapper();
        $persistenceKey = "view:{$cacheKey}";

        // 🎼 RBN Framework: [RBN Framework FALLBACK FOR COMMON views] - Direct load if file exists in RbnCommon
        $filename = basename($viewBase);
        foreach ($extensions as $ext) {
            $commonPath = $frameworkViews . DIRECTORY_SEPARATOR . 'RbnCommon' . DIRECTORY_SEPARATOR . $filename . $ext;
            if (file_exists($commonPath)) {
                $mapper->set($persistenceKey, $commonPath);
                return self::$resolveCache[$cacheKey] = $commonPath;
            }
        }

        if ($persistedPath = $mapper->get($persistenceKey)) {
            if (file_exists($persistedPath)) {
                return self::$resolveCache[$cacheKey] = $persistedPath;
            }
        }

        foreach ($targets as $dir) {
            foreach ($extensions as $ext) {
                $path = $dir . DIRECTORY_SEPARATOR . $viewBase . $ext;
                if (file_exists($path)) {
                    // Save to Persistence Layer for lightning speed next time ⚡
                    $mapper->set($persistenceKey, $path);
                    return self::$resolveCache[$cacheKey] = $path;
                }
            }
        }

        return self::$resolveCache[$cacheKey] = null;
    }
}
