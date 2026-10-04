<?php
$context = $context ?? 'frontend';
?>
    <script>
        (function () {
            'use strict';

            // 1. Universal rbnReady Event Queue 🚦
            window._rbnQueue = window._rbnQueue || [];
            window.rbnReady = window.rbnReady || function (fn) {
                if (window._rbnReadyProcessed) {
                    try { fn(); } catch (e) { console.error(e); }
                } else {
                    window._rbnQueue.push(fn);
                }
            };

            <?php if ($context === 'panel' || $context === 'admin'): ?>
// 2. Admin Panel Sidebar Engine 📐
            window.rbnInitSidebar = function (sidebarId, categoriesSelector) {
                try {
                    sidebarId = sidebarId || 'sidebar';
                    categoriesSelector = categoriesSelector || '#sidebar .rbn-dash-category';
                    var sb = document.getElementById(sidebarId);
                    if (sb && (localStorage.getItem('rbn_admin_sidebar_collapsed') === 'true')) {
                        sb.classList.add('collapsed');
                    }
                    var cats = JSON.parse(localStorage.getItem('rbn_admin_sidebar_categories') || '{}');
                    var items = document.querySelectorAll(categoriesSelector);
                    for (var i = 0; i < items.length; i++) {
                        var c = items[i];
                        var id = c.getAttribute('data-category-id') || c.querySelector('.rbn-dash-section-header span')?.textContent?.trim();
                        if (id && cats[id] === true) {
                            c.classList.add('closed');
                        }
                    }
                } catch (e) { }
            };
            <?php endif; ?>

            <?php if ($context === 'frontend'): ?>
// 2. Frontend Tema Durumu (Dark/Light SSoT) 🎨
            try {
                var themeKey = document.documentElement.getAttribute('data-theme-key') || 'rbn_theme';
                var savedTheme = localStorage.getItem(themeKey);
                if (savedTheme) {
                    document.documentElement.setAttribute('data-theme', savedTheme);
                }
            } catch (e) { }
            <?php endif; ?>
        })();
    </script>