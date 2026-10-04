/**
 * ==========================================================================
 * RBN FRAMEWORK MASTER JAVASCRIPT SUITE (rbn-master.js) 🏛️💎
 * The Sovereign Master Script Index for all RBN Applications.
 * Combines: Ready Queue Engine + Dynamic Core Module Loader
 * ==========================================================================
 */
(function (window, document) {
    'use strict';

    /* ========================================================
       1. CORE: rbnReady Queue Engine ⏳
       ======================================================== */
    window._rbnQueue = window._rbnQueue || [];
    window._rbnReadyProcessed = false;

    function rbnReady(fn) {
        if (typeof fn !== 'function') return;

        if (window._rbnReadyProcessed && document.readyState !== 'loading') {
            fn();
        } else {
            window._rbnQueue.push(fn);
        }
    }

    function processReadyQueue() {
        window._rbnReadyProcessed = true;
        const process = () => {
            while (window._rbnQueue.length > 0) {
                const fn = window._rbnQueue.shift();
                try {
                    fn();
                } catch (e) {
                    console.error('rbnReady callback error:', e);
                }
            }
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', process);
        } else {
            process();
        }
    }

    window.rbnReady = rbnReady;
    window.rbnProcessQueue = processReadyQueue;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', processReadyQueue);
    } else {
        processReadyQueue();
    }

    /* ========================================================
       2. DYNAMIC CORE MODULE LOADER (Like rbn-master.css) 🧩🚀
       ======================================================== */
    const currentScript = document.currentScript;
    const basePath = currentScript 
        ? currentScript.src.substring(0, currentScript.src.lastIndexOf('/')) 
        : '/framework-assets/rbncommon/js';

    const coreModules = [
        'core/rbnDom.js',
        'core/rbnComponents.js',
        'Utils/rbnUtils.js',
        'Utils/rbnBinders.js',
        'Utils/rbnFile.js',
        'components/rbnAlert.js',
        'components/rbnModal.js',
        'components/rbnTable.js',
        'Networking/rbnService.js'
    ];

    coreModules.forEach(module => {
        const script = document.createElement('script');
        script.src = `${basePath}/${module}`;
        script.async = false; // Sıralı çalıştırma koruması
        document.head.appendChild(script);
    });

})(window, document);
