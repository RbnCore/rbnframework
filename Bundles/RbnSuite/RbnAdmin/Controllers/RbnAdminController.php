<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Controllers;

use Rbn\Framework\Core\Base\Web\BaseController;
use Rbn\Framework\Core\Base\Attributes\Module;
use Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Models\ModuleData;

/**
 * RbnAdminController - The Sovereign Root for Admin Suite 🏰🛰️⚓
 * 
 * RBN 3.5 Masterpiece Standard.
 * This class serves as the root identity for the RbnAdmin module ecosystem.
 */
#[Module(
    name: 'rbnadmin',
    data: ModuleData::class,
    context: 'backend'
)]
class RbnAdminController extends BaseController
{
    /**
     * Admin Dashboard Gateway 🏛️⚓
     */
    public function adminIndex()
    {
        return $this->renderDashboard('admin');
    }

    /**
     * Otonom Proje Geçiş Gateway'i (Zero Query Param / Clean Redirect) 🔄🛰️⚓
     */
    public function switchProject(string $key)
    {
        $this->switchProjectContext($key);
        $this->service('sidebar')->clearSidebarCache();

        $referer = $_SERVER['HTTP_REFERER'] ?? $this->Route->url('dashboard', 'admin');
        $cleanUrl = strtok($referer, '?');

        return $this->response->redirect($cleanUrl);
    }

    /**
     * User Dashboard Gateway 👤⚓
     */
    public function userIndex()
    {
        return $this->renderDashboard('user');
    }

    /**
     * Internal Dashboard Orchestrator 🎼🛰️⚓
     * Directly communicates with dashboardManager service if available.
     */
    protected function renderDashboard(string $panel)
    {
        // 🛡️ Otonom Akış: Doğrudan 'dash.stats' sağlayıcısını (provider) sorgula
        $dashStats = [];
        $dashProvider = $this->provider('dash.stats');
        if ($dashProvider) {
            $projectKey = active_project_key();
            $method = 'get' . ucfirst($projectKey) . 'Stats';

            if (method_exists($dashProvider, $method)) {
                $dashStats = $dashProvider->{$method}();
            } elseif (method_exists($dashProvider, 'getAdminStats')) {
                $dashStats = $dashProvider->getAdminStats();
            }
        }

        // 🌐 RBN System Core: Automatic Central Web Traffic Summary Injection 🏗️⚓
        $analytics = $this->service('analytics');
        $trafficSummary = $analytics ? $analytics->traffic()->summary() : [
            'active_now' => 0,
            'today_hits' => 0,
            'total_hits' => 0,
        ];

        // 🌍 GeoHelper: ISO Ülke kodlarını (Örn: 'SG' -> 'Singapur', 'US' -> 'A.B.D') çözümlü isim ve bayrağa dönüştür
        if (!empty($trafficSummary)) {
            $geoHelper = $this->helper('Geo');
            $countryFlags = $geoHelper ? $geoHelper->countryFlags() : [];

            if (isset($trafficSummary['top_location'])) {
                $topLoc = strtoupper(trim((string) $trafficSummary['top_location']));
                $name = $countryFlags[$topLoc]['name'] ?? $topLoc;
                if ($topLoc === 'US' || $name === 'Amerika Birleşik Devletleri') {
                    $name = 'A.B.D';
                }
                $trafficSummary['top_location_name'] = $name;
                $trafficSummary['top_location_flag'] = isset($countryFlags[$topLoc]) ? 'fi fi-' . strtolower($topLoc) . ' flag-icon flag-icon-' . strtolower($topLoc) : null;
            }

            if (isset($trafficSummary['top_location_today'])) {
                $topLocToday = strtoupper(trim((string) $trafficSummary['top_location_today']));
                $nameToday = $countryFlags[$topLocToday]['name'] ?? $topLocToday;
                if ($topLocToday === 'US' || $nameToday === 'Amerika Birleşik Devletleri') {
                    $nameToday = 'A.B.D';
                }
                $trafficSummary['top_location_today_name'] = $nameToday;
                $trafficSummary['top_location_today_flag'] = isset($countryFlags[$topLocToday]) ? 'fi fi-' . strtolower($topLocToday) . ' flag-icon flag-icon-' . strtolower($topLocToday) : null;
            }
        }

        $data = [
            'stats' => array_merge($trafficSummary, $dashStats)
        ];

        // 🎻 RBN 3.5: Group Projects Discovery (Unified Caching)
        $groupProjects = group_projects();

        // 🎻 RBN 3.5: Dynamic App Name / Project Group Logic
        $rawAppName = (!empty($groupProjects) && count($groupProjects) > 1) ? $this->projectGroup : $this->appName;
        $data['stats']['app_name'] = mb_convert_case((string) ($rawAppName ?? 'RBN Framework'), MB_CASE_TITLE, 'UTF-8');
        $data['stats']['app_version'] = app_version();

        // Compute active project key and display name for dashboard view
        $projectKey = active_project_key();
        $activeProjectName = $projectKey;
        $projectDomain = '';

        if (!empty($groupProjects)) {
            foreach ($groupProjects as $gProj) {
                if ($gProj['project_key'] === $projectKey) {
                    $activeProjectName = $gProj['project_name'] ?? $projectKey;
                    $projectDomain = $gProj['domain'] ?? '';
                    break;
                }
            }
        }

        // Fallback to active system variables only if masterdb record is not found
        if (empty($projectDomain)) {
            $projectDomain = $data['stats']['site_domain'] ?? site_domain();
        }

        // 🎼 RBN 3.5: Automatic AI Telemetry Cards on Dashboard when bot_activity is active
        $botActive = (int) $this->service('shieldSettings')->getSetting('bot_activity', 0) === 1;
        $aiReport = null;
        if ($botActive) {
            $aiReport = $this->manager('aiUsage')->getFilteredReport(['project' => $projectKey]);
        }

        // 2. Render Orchestration 🎨⚓
        // Projede veya framework içinde Resources/Views/RbnAdmin/dashboard.rbn.php basılır.
        return $this->render('RbnAdmin/dashboard', array_merge([
            'panel' => $panel,
            'module' => null, // 🎼 RBN 3.5: [SOVEREIGN] No project-module required
            'groupProjects' => $groupProjects,
            'projectKey' => $projectKey,
            'activeProjectName' => $activeProjectName,
            'projectDomain' => $projectDomain,
            'botActive' => $botActive,
            'aiReport' => $aiReport
        ], $data));
    }

    /**
     * Saves theme colors and clears related system cache.
     * 
     * @return mixed
     */
    public function saveTheme()
    {
        // 1. FormRequest ile Güvenli Veri Alımı
        $data = $this->request->form([
            'theme_color_primary' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
            'theme_color_secondary' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'project' => 'nullable|string'
        ]);

        $projectKey = $data['project'] ?? null;

        // 2. İş Mantığı İçin Servise Gönder
        $result = $this->service->saveColors(
            $data['theme_color_primary'],
            $data['theme_color_secondary'] ?? null,
            $projectKey
        );

        // 3. Rota ve Sonuç Yönetimi
        return $this->Route->handleResult($result);
    }
}
