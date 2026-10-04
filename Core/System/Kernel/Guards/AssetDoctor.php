<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\System\Kernel\Guards;

use Rbn\Framework\Core\Render\Controllers\AssetController;
use Rbn\Framework\Core\Render\Configs\AssetConfig;

use Rbn\Framework\Core\System\Kernel\Base\BaseGuard;

/**
 * AssetDoctor - The Fast-Path Dispatcher 🩺⚡
 * 
 * Part of the Pre-Flight Diagnostic & Dispatching chain.
 * Detects and serves static assets before the full Kernel boots up.
 */
class AssetDoctor extends BaseGuard
{
    /**
     * Check if the current request is an asset and serve it.
     */
    public static function check(): void
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (empty($uri))
            return;

        // 1. Scan & Detect
        $cleanUri = trim((string)parse_url($uri, PHP_URL_PATH), '/');

        // 2. Resolve Asset Scopes via Asset Configuration Hub 🛰️🏛️
        $setup = AssetConfig::PROXY_SETUP;
        $fwPrefix = ($setup['framework']['path'] ?? 'framework-assets') . '/';
        $prPrefix = ($setup['project']['path'] ?? 'project-assets') . '/';

        $isFrameworkAsset = strpos($cleanUri, $fwPrefix) === 0;
        $isProjectAsset = strpos($cleanUri, $prPrefix) === 0;

        if (!$isFrameworkAsset && !$isProjectAsset)
            return;

        // 3. Dispatch Fast Serve via Instance 🛰️
        if (class_exists(AssetController::class)) {
            // [RBN 3.5] SIGNAL SURVIVAL CONTEXT 🚩
            // Inform the Config hub that we are in Fast-Path / Pre-Boot stage.
            // This prevents the BaseController from triggering a circular DB loop.
            \Rbn\Framework\Core\System\Config\Config::setSurvivalMode(true);

            try {
                $path = $isFrameworkAsset ? substr($cleanUri, strlen($fwPrefix)) : substr($cleanUri, strlen($prPrefix));
                (new AssetController())->serve($path, $isProjectAsset);
            } finally {
                \Rbn\Framework\Core\System\Config\Config::setSurvivalMode(false);
            }
        }
    }
}
