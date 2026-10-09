/**
 * ==========================================================================
 * RBN ADMIN MASTER JAVASCRIPT SUITE (rbnAdmin.js) 💻🛰️⚓
 * The RBN Framework Master Script Index for RBN Admin Dashboard.
 * Dynamically boots admin controllers, debugging bridges and AI services.
 * ==========================================================================
 */
(function (window, document) {
    'use strict';

    const currentScript = document.currentScript;
    const basePath = currentScript 
        ? currentScript.src.substring(0, currentScript.src.lastIndexOf('/')) 
        : '/framework-assets/rbnadmin/js';

    // RBN Admin RBN Framework Modules
    let commonPath = '/framework-assets/rbncommon/js';
    let adminPath = '/framework-assets/rbnadmin/js';

    const adminModules = [
        `${adminPath}/rbnAdminApp.js`,
        `${commonPath}/Networking/DebugBridge.js`,
        `${commonPath}/Utils/rbnAi.js`
    ];

    adminModules.forEach(src => {
        const script = document.createElement('script');
        script.src = src;
        script.async = false; // Preserve execution order
        document.head.appendChild(script);
    });

})(window, document);
