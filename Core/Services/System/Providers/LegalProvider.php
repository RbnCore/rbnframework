<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\System\Providers;

use Rbn\Framework\Core\Base\Services\BaseProvider;
use Rbn\Framework\Core\System\Paths\Paths;

/**
 * LegalProvider - Data Engine for Legal & Compliance Service 🏛️🛰️⚓
 */
class LegalProvider extends BaseProvider
{
    /**
     * Renders the standard Cookie Consent notice if no consent is found. 🛰️⚓
     */
    public function render(?string $view, array $data, string $type): string
    {
        // 1. Consent Check (Rıza verilmişse basma)
        if (isset($_COOKIE['rbn_cookie_consent']) && $_COOKIE['rbn_cookie_consent'] === 'accepted') {
            return '';
        }

        // 2. Resolve settings
        $mapping = $this->getActiveRouteMapping();
        $cookieDisabled = isset($mapping['cookie']) && $mapping['cookie'] === false;

        if ($cookieDisabled) {
            return '';
        }

        $policyUrl = $mapping['cookie_policy_url'] ?? '/sayfa/cerez-politikasi';
        if (!str_starts_with($policyUrl, '/') && !str_starts_with($policyUrl, 'http')) {
            $policyUrl = '/' . $policyUrl;
        }

        // 3. Render Centralized System View 🏛️🏺⚓
        ob_start();
        $engine = $this->service('render')->engine();
        $viewPath = Paths::frameworkRoot() . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'RbnCommon' . DIRECTORY_SEPARATOR . 'Components' . DIRECTORY_SEPARATOR . 'cookie_notice.rbn.php';

        if (file_exists($viewPath)) {
            $engine->render($viewPath, [
                'policyUrl' => $policyUrl,
                'consentKey' => 'rbn_cookie_consent',
                'cookieDisabled' => false
            ]);
        }

        return (string) ob_get_clean();
    }

    /**
     * Resolves and caches the active project mapping from routemap. 🗺️
     */
    private function getActiveRouteMapping(): array
    {
        static $mapping = null;
        if ($mapping !== null) {
            return $mapping;
        }

        $projectKey = function_exists('project_key') ? project_key() : '';
        $mapping = (array) ($this->getRouteConfig($projectKey) ?? []);

        return $mapping;
    }
}
