<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Definitions\Render;

use Rbn\Framework\Core\Support\Definitions\Render\AssetDefinition as AD;

/**
 * AssetBundles - The Asset Architect 🏛️🛰️⚓
 * 
 * RBN Framework Architecture.
 * This file handles groupings, context-aware stacks, and mapping exceptions.
 */
class AssetBundles
{
    /**
     * CORE_STACKS - Context-aware "Always-on" library references. 🧬
     * RBN Framework: Stacks now point to BUNDLE names to ensure zero repetition.
     */
    public const STACK_MAP = [
        'universal' => ['bootstrap', 'font_awesome', 'bootstrap_icons', 'remix_icon', 'rbn_master_js'],
        'frontend' => ['rbn_core_frontend'],
        'panel' => ['fonts_panel', 'jquery', 'sortable', 'rbn_core_panel', 'rbnTable'],
        'auth' => ['fonts_auth', 'rbn_core_auth']
    ];

    /**
     * BUNDLES - Master collection of atomic and feature-set packages. 🎡
     * This is the single source of truth for all asset groupings.
     */
    public const BUNDLES = [
        // 🎼 Atomic System Bundles (Shared)
        'rbn_master_js' => ['scripts' => [AD::RBN_MASTER_JS]],

        'fonts_panel' => ['styles' => ['@font/Plus Jakarta Sans', '@font/DM Serif Display', '@font/JetBrains Mono']],
        'fonts_auth' => ['styles' => ['@font/Plus Jakarta Sans']],
        'bootstrap' => ['styles' => [AD::BOOTSTRAP_CSS], 'scripts' => [AD::BOOTSTRAP_JS]],
        'font_awesome' => ['styles' => [AD::FONT_AWESOME]],
        'bootstrap_icons' => ['styles' => [AD::BOOTSTRAP_ICONS]],
        'remix_icon' => ['styles' => [['path' => AD::REMIX_ICON, 'attributes' => AD::REMIX_ICON_ATTRS]]],
        'flag_icon' => ['styles' => [AD::FLAG_ICON]],
        'jquery' => ['scripts' => [AD::JQUERY]],

        // 🛠️ RBN Role-Based Core Tools 🧬
        'admin_core_css' => ['styles' => [AD::RBN_ADMIN_VARIABLES_CSS, AD::RBN_ADMIN_GLOBAL_CSS]], // Lightweight Panel Styles (Variables & Grid)
        'rbn_core_frontend' => [
            'styles' => [AD::RBN_ALERT_CSS, AD::RBN_COMMON_CSS, '@project/css/master.css'],
        ],

        'rbn_core_panel' => [
            'styles' => [AD::RBN_MASTER_CSS, AD::RBN_DASHBOARD_CSS, AD::RBN_ADMIN_CSS],
            'scripts' => [
                AD::RBN_ADMIN_JS,
            ]
        ],
        'rbn_core_auth' => [
            // 🎼 RBN Core Master Engine: Master CSS + Auth Identity Suite CSS
            // rbn-auth.css, eskiden `Resources/Views/RbnAuth/Layouts/auth_header.rbn.php`
            // icindeki view-içi `<style>` bloguydu; motor-once kurali geregi buraya tasindi.
            'styles' => [
                AD::RBN_MASTER_CSS,
                AD::RBN_AUTH_CSS,
            ]
        ],

        // 📦 OPT-IN CSS PACKETS (core/rbn-master.css kapanisinin DISINDA) 🧾
        // Bu paketler HER sayfaya otomatik yuklenMEZ. Bir proje veya gorunum
        // acikca istemedigi surece hicbir sayfaya girmez -> mevcut sayfalarin
        // davranisi degismez. Iste: `assets => ['rbnExtended']` (Map) veya
        // `$this->addAsset('rbnExtended')` (Controller).
        'rbnExtended' => [
            'styles' => [AD::RBN_EXTENDED_UTILITIES_CSS, AD::RBN_EXTENDED_COMPONENTS_CSS],
        ],

        // 🛰️ Feature Bundles (Atomic & Modular Enhancements)
        'rbnModal' => ['styles' => [AD::RBN_MODAL_CSS], 'scripts' => [AD::RBN_MODAL_JS]],
        'rbnDashboard' => ['styles' => [AD::RBN_DASHBOARD_CSS]],
        'rbnCharts' => ['styles' => [AD::RBN_CHARTS_CSS], 'scripts' => [AD::RBN_CHARTS_JS]],
        'rbnTable' => ['styles' => [AD::RBN_TABLE_CSS], 'scripts' => [AD::RBN_TABLE_JS]],
        'sortable' => ['scripts' => [AD::SORTABLE_JS]],
        'aos' => ['styles' => [AD::AOS_CSS], 'scripts' => [AD::AOS_JS]],
        'rbn_master' => [
            'styles' => [AD::RBN_MASTER_CSS],
            'scripts' => [AD::RBN_MASTER_JS]
        ],
        'security' => ['scripts' => [AD::RBN_ADMIN_APP]],

        // 🪐 Project Custom Space
        'project' => ['styles' => ['@project/master.css']],
        'frontend' => [] // Base frontend alias (Placeholder)
    ];
}
