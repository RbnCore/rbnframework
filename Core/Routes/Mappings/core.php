<?php

use Rbn\Framework\Core\Routes\Route;

/**
 * CORE ROUTES - Framework Seviyesi Rotalar
 * 
 * Bu dosya framework'ün temel işleyişi için gerekli olan rotaları içerir.
 */

// 1. Framework Controllers Namespace
Route::
        namespace('Rbn\Framework\Core\Controllers')->group(function () {
            // Other generic framework controllers would go here
        });

// 2. SEO & Sitemap (via Core Render) 🔍
// Merged into Group 3

// 3. Exception & Error Services ⚙️🛡️ (Zero-Controller Logic)
Route::get('403', function () {
    shield()->abort(403);
});
Route::get('500', function () {
    shield()->abort(500);
});

// 4. System Assets & SEO (via Core Render) 🏙️🎨
Route::
        namespace('Rbn\Framework\Core\Render\Controllers')->group(function () {

            // SEO Routes via Sovereign Crawler Hub 🤖🗺️⚓
            Route::get('feed', 'CrawlerController@feed');
            Route::get('sitemap-{type}.xml', 'CrawlerController@subSitemap');
            Route::get('sitemap.xml', 'CrawlerController@sitemap');
            Route::get('sitemap.xsl', 'CrawlerController@sitemapXsl');
            Route::get('robots.txt', 'CrawlerController@robots');
            Route::get('humans.txt', 'CrawlerController@humans');
            Route::get('security.txt', 'CrawlerController@security');
            Route::get('.well-known/security.txt', 'CrawlerController@security');
            Route::get('llms.txt', 'CrawlerController@llms');
            Route::get('ai.txt', 'CrawlerController@llms');
            Route::get('{key:[a-f0-9]{64}}.txt', 'CrawlerController@indexNowKey');

            // Framework Assets Proxy
            Route::prefix('framework-assets')->get('{path:.+}', 'AssetController@serve');
            // Project Assets Proxy (Dynamic)
            Route::prefix('project-assets')->get('{path:.+}', 'AssetController@serveProject');

            // Secure File Proxy (Uploads & Exports)
            Route::prefix('fw-proxy')->group(function () {
                Route::get('upload/{path:.+}', 'FileProxyController@serveUpload');
                Route::get('export/{path:.+}', 'FileProxyController@serveExport');
            });
        });

// --- SOVEREIGN BACKSTAGE ORCHESTRATION 🛰️🪐⚓ ---

// ⚡ [RBN 3.5] Smart Developer Panel - Developer Authority (Session + Role) 🛠️
Route::middleware('developer')->panel('developer')->group(function () {
    // 🚀 Backstage (Developer Merkezi) 🛰️🪐⚓
    Route::module('internal', 'Backstage')->load();
});

// ⚡ [RBN 3.5] Smart Assistant Panel - Super Admin & Developer 🛠️
Route::middleware('superadmin')->panel('developer')->group(function () {
    // 🌐 Web Hub (Frontend Identity, SEO & Navigation) 🏛️⚓
    Route::module('internal', 'Webhub')->load();

    // 🚀 Syshub (Sistem Merkezi) 🏛️🛰️⚓
    Route::module('internal', 'Syshub')->load();
});

// ⚡ [RBN 3.5] Clean Wrapper - Admin Authority (Session + Lock + Role: Admin) 🔱🏛️
Route::middleware('admin')->group(function () {
    // 🎷 [UNIVERSAL SOVEREIGN GATEWAY] Dedicated dispatcher for centralized AJAX modals
    Route::get('admin/modal/content', [\Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Controllers\ModalController::class, 'content']);

    // 2. ADMIN MANAGEMENT PANEL 🔱🏛️
    Route::panel('admin')->group(function () {
        // RbnAdmin Core Suite & Webtraffic 🚀
        Route::module('suite', 'RbnAdmin')->load();

        // RbnStudio Core Suite 🎨🚀
        Route::module('suite', 'RbnStudio')->load();
    });
});