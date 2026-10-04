<?php

declare(strict_types=1);

use Rbn\Framework\Core\Routes\Route;
use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * Unified App Routes - (Mirror Hub v5.0 Masterpiece) 🎼🛰️⚓
 */

// 0. Static Route Map Cache 🧠⚡
static $routeMapCache = [];

// 0. Dinamik Dış API Endpoint'i 🚀
Route::middleware(\Rbn\Framework\Core\Http\Security\ApiGuard::class)->group(function () {
    Route::any('/api/v1/external', 'Rbn\Framework\Core\Render\Controllers\Api\ExternalApiController@index');
});

// 1. Frontend & Public Discovery (Key-Based Sovereign Dispatch) 🌍
$projectData = \Rbn\Framework\Core\System\Kernel\Bootstrap::getAppContext('project_data');
$projectKey = $projectData['project_key'] ?? project_key();

$moduleName = BaseService::get()->getRouteConfig($projectKey, 'module') ?: 'Frontend';
$config = BaseService::get()->getRouteConfig($projectKey) ?: [];

Route::module('frontend', $moduleName)->prefix('')->group(function () {
    Route::controller('HomeController')->group(function () {
        Route::get('sayfa/{slug}', 'page');
        Route::get('sik-sorulan-sorular', 'faqs');
    });
});
Route::module('frontend', $moduleName)->prefix('')->load();

$routeService = BaseService::get()->service('route');
$panelPrefix = $routeService ? $routeService->getBasePrefix() : 'dashboard';

if (($config['panel'] ?? true) === false) {
    $uri = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '', '/');

    // [R-14] `in_array(...)` TAM eslesme ile calisiyordu: `/rbn-admin/alt`,
    // `/kayit/2/sifre` gibi ALT yollar korumayi GECIYORDI. Ayni isi dogru
    // yapan, segment-bazli blok `RouteManager::checkProjectQueryGuard()`
    // icinde zaten var (`:346-353`) — iki kaynak birbirinden ayristirildi.
    // Kapsam genisler: alt yollar da kapatilir. `/rbn-admin-benzeri` gibi
    // onek-benzeri yollar KAPSAM DEGIL (segment sonu siniri).
    $panelKapaliKapsam = str_starts_with($uri, $panelPrefix) || $uri === 'giris';
    if (!$panelKapaliKapsam) {
        foreach (\Rbn\Framework\Core\Support\Definitions\Route\RouteBlueprint::AUTH_ROOTS as $page) {
            if ($uri === $page || str_starts_with($uri, $page . '/')) {
                $panelKapaliKapsam = true;
                break;
            }
        }
    }

    if ($panelKapaliKapsam) {
        response()->redirect('/');
    }
}
