<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Definitions\Render;

/**
 * AssetDefinition - The Master Asset Registry 🎭⚓
 * 
 * RBN 3.5 "Masterpiece" Architecture.
 * This file is a PURE REGISTRY of individual asset constants (Paths/CDNs).
 */
class AssetDefinition
{
    /**
     * CDNs & EXTERNAL LIBRARIES 🌐
     */
    public const BOOTSTRAP_CSS = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css';
    public const BOOTSTRAP_JS = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js';

    public const FONT_AWESOME = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css';
    public const BOOTSTRAP_ICONS = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css';
    public const REMIX_ICON = 'https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css';
    public const FLAG_ICON = 'https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/6.6.6/css/flag-icons.min.css';

    public const JQUERY = ['path' => 'https://code.jquery.com/jquery-3.7.1.min.js', 'renderInHead' => true];
    public const SORTABLE_JS = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js';
    public const AOS_CSS = 'https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css';
    public const AOS_JS = 'https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js';

    /**
     * RBN_ADMIN - Dashboard Suite (@fw) 💻⚓
     */
    public const RBN_ADMIN_CSS = '@fw/RbnAdmin/css/rbnAdmin.css';
    public const RBN_ADMIN_JS = '@fw/RbnAdmin/js/rbnAdmin.js';
    public const RBN_ADMIN_VARIABLES_CSS = '@fw/RbnAdmin/css/variables.css';
    public const RBN_ADMIN_GLOBAL_CSS = '@fw/RbnAdmin/css/global.css';
    public const RBN_ADMIN_APP = '@fw/RbnAdmin/js/rbnAdminApp.js';


    /**
     * RBN_COMMON - Shared Interface Assets (@fw) 🌐🛰️
     */
    public const RBN_MASTER_JS = '@fw/RbnCommon/js/rbn-master.js';
    public const RBN_COMMON_CSS = '@fw/RbnCommon/css/rbn-common.css';
    public const RBN_MASTER_CSS = '@fw/RbnCommon/css/rbn-master.css';
    public const RBN_SHIELD_CSS = '@fw/RbnCommon/css/rbn-shield.css';
    public const RBN_DASHBOARD_CSS = '@fw/RbnCommon/css/core/dashboard.css';
    public const RBN_AUTH_CSS = '@fw/RbnCommon/css/core/rbn-auth.css';

    /**
     * OPT-IN CSS PACKAGES (core/rbn-master.css kapanisina DAHIL DEGILDIR)
     * Yalnizca proje/gorunum acikca paket adini istediginde yuklenir.
     */
    public const RBN_EXTENDED_UTILITIES_CSS = '@fw/RbnCommon/css/optional/rbn-utilities-extended.css';
    public const RBN_EXTENDED_COMPONENTS_CSS = '@fw/RbnCommon/css/optional/rbn-components-extended.css';

    /**
     * RBN_COMMON - Core Components, Networking & Tools (@fw) 🛠️🛰️
     */
    public const RBN_SERVICE = '@fw/RbnCommon/js/Networking/rbnService.js';
    public const RBN_DEBUG_BRIDGE = '@fw/RbnCommon/js/Networking/DebugBridge.js';
    public const RBN_ALERT_JS = '@fw/RbnCommon/js/components/rbnAlert.js';
    public const RBN_ALERT_CSS = '@fw/RbnCommon/css/components/rbnAlert.css';
    public const RBN_MODAL_JS = '@fw/RbnCommon/js/components/rbnModal.js';
    public const RBN_MODAL_CSS = '@fw/RbnCommon/css/components/rbnModal.css';
    public const RBN_UTILS = '@fw/RbnCommon/js/Utils/rbnUtils.js';
    public const RBN_BINDERS = '@fw/RbnCommon/js/Utils/rbnBinders.js';
    public const RBN_AI = '@fw/RbnCommon/js/Utils/rbnAi.js';
    public const RBN_CHARTS_JS = '@fw/RbnCommon/js/components/rbnCharts.js';
    public const RBN_CHARTS_CSS = '@fw/RbnCommon/css/components/rbnCharts.css';
    public const RBN_TABLE_JS = '@fw/RbnCommon/js/components/rbnTable.js';
    public const RBN_TABLE_CSS = '@fw/RbnCommon/css/components/rbnTable.css';


}
