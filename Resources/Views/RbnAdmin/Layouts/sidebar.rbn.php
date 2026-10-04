<!-- Sovereign RBN Dashboard Sidebar Component 🏛️ -->
<nav class="rbn-dash-sidebar" id="sidebar">
    <!-- Header / Brand -->
    <div class="rbn-dash-sidebar-header">
        <a href="javascript:void(0)" class="rbn-dash-sidebar-brand" onclick="rbnAdminToggleSidebar()">
            <i class="ri-code-s-slash-line"></i>
            <span>@sys('ADMIN_NAME') <small class="ms-1"
                    style="font-size: 0.7rem; font-weight: 500;">v@sys('ADMIN_VERSION')</small></span>
        </a>
    </div>

    <!-- Navigation Scroll Area -->
    <div class="rbn-dash-sidebar-nav">
        <!-- Core Navigation -->
        <div class="rbn-dash-category">
            <div class="rbn-dash-nav-item">
                <a href="<?= $Route->url('dashboard', 'admin') ?>"
                    class="rbn-dash-nav-link <?= $Route->isActive('dashboard', 'admin', 'dashboard', true) ? 'active' : '' ?>">
                    <i class="ri-dashboard-3-line"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            <div class="rbn-dash-nav-item">
                <a href="<?= $Route->url('webtraffic', [], 'admin', 'RbnAdmin') ?>"
                    class="rbn-dash-nav-link <?= $Route->isActive('webtraffic', 'admin', 'RbnAdmin') ? 'active' : '' ?>">
                    <i class="ri-line-chart-line"></i>
                    <span>Webtraffic Analitik</span>
                </a>
            </div>
            <div class="rbn-dash-nav-item">
                <a href="<?= $Route->url('hostmailhub', [], 'admin', 'RbnAdmin') ?>"
                    class="rbn-dash-nav-link <?= $Route->isActive('hostmailhub', 'admin', 'RbnAdmin') ? 'active' : '' ?>">
                    <i class="ri-mail-line"></i>
                    <span>E-Posta Yönetimi</span>
                </a>
            </div>
            <?php
            $botActive = (int) $Route->service('shieldSettings')->getSetting('bot_activity', 0) === 1;
            if ($botActive):
                ?>
                <div class="rbn-dash-nav-item">
                    <a href="<?= $Route->url('bot-settings', [], 'admin', 'RbnAdmin') ?>"
                        class="rbn-dash-nav-link <?= $Route->isActive('bot-settings', 'admin', 'RbnAdmin') ? 'active' : '' ?>">
                        <i class="ri-robot-line"></i>
                        <span>Bot & API Ayarları</span>
                    </a>
                </div>
                <div class="rbn-dash-nav-item">
                    <a href="<?= $Route->url('admin.cron.index') ?>"
                        class="rbn-dash-nav-link <?= $Route->isActive('cron', 'admin', 'RbnAdmin') || $Route->isActive('cronlogs', 'admin', 'RbnAdmin') ? 'active' : '' ?>">
                        <i class="ri-time-line"></i>
                        <span>Cron Ayarları</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- RBN STUDIO Module -->
        <?php
        $pKey = method_exists($Route, 'activeProjectKey') ? $Route->activeProjectKey() : (function_exists('project_key') ? project_key() : null);
        $contentConf = (array) ($Route->getRouteConfig($pKey, 'content') ?? []);
        $hasBlog = !empty($contentConf['blog']);
        $hasNews = !empty($contentConf['news']);
        $hasCategory = !empty($contentConf['category']);
        $hasDraft = !empty($contentConf['draft']);

        if ($hasBlog || $hasNews || $hasCategory || $hasDraft):
            ?>
            <div class="rbn-dash-category mt-2" data-category-id="rbn_studio">
                <div class="rbn-dash-section-header" onclick="rbnAdminToggleCategory(this)">
                    <span>RBN STUDIO</span>
                    <i class="ri-arrow-down-s-line rbn-dash-arrow"></i>
                </div>
                <div class="rbn-dash-category-menus">
                    <?php if ($hasCategory): ?>
                        <div class="rbn-dash-nav-item">
                            <a href="<?= $Route->url('admin.studio.categories.index') ?>"
                                class="rbn-dash-nav-link <?= $Route->isActive('studio/categories', 'admin') ? 'active' : '' ?>">
                                <i class="ri-folder-line"></i>
                                <span>Kategoriler</span>
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if ($hasDraft): ?>
                        <div class="rbn-dash-nav-item">
                            <a href="<?= $Route->url('admin.studio.drafts.index') ?>"
                                class="rbn-dash-nav-link <?= $Route->isActive('studio/drafts', 'admin') ? 'active' : '' ?>">
                                <i class="ri-lightbulb-line"></i>
                                <span>Fikirler & Taslaklar</span>
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if ($hasBlog): ?>
                        <div class="rbn-dash-nav-item">
                            <a href="<?= $Route->url('admin.studio.posts.index') ?>"
                                class="rbn-dash-nav-link <?= $Route->isActive('studio/posts', 'admin') ? 'active' : '' ?>">
                                <i class="ri-article-line"></i>
                                <span>Yayınlanan Makaleler</span>
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if ($hasNews): ?>
                        <div class="rbn-dash-nav-item">
                            <a href="<?= $Route->url('admin.studio.news.index') ?>"
                                class="rbn-dash-nav-link <?= $Route->isActive('studio/news', 'admin') ? 'active' : '' ?>">
                                <i class="ri-newspaper-line"></i>
                                <span>Yayınlanan Haberler</span>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Dynamic Group & Module Items -->
        <?php if (!empty($sidebarItems)): ?>
            <?php foreach ($sidebarItems as $categoryIndex => $category): ?>
                <div class="rbn-dash-category" data-category-id="<?= $categoryIndex ?>">
                    <div class="rbn-dash-section-header" onclick="rbnAdminToggleCategory(this)">
                        <span><?= htmlspecialchars((string) $category['name']) ?></span>
                        <i class="ri-arrow-down-s-line rbn-dash-arrow"></i>
                    </div>

                    <div class="rbn-dash-category-menus">
                        <?php if (!empty($category['menus'])): ?>
                            <?php foreach ($category['menus'] as $menu): ?>
                                <?php if (!empty($menu['children'])): ?>
                                    <div class="rbn-dash-nav-item rbn-dash-dropdown">
                                        <a href="javascript:void(0)" class="rbn-dash-nav-link" onclick="rbnAdminToggleDropdown(this)">
                                            <i class="<?= htmlspecialchars((string) $menu['menu_icon']) ?>"></i>
                                            <span><?= htmlspecialchars((string) $menu['menu_title']) ?></span>
                                            <i class="ri-arrow-down-s-line rbn-dash-arrow ms-auto"></i>
                                        </a>
                                        <div class="rbn-dash-dropdown-menu">
                                            <?php foreach ($menu['children'] as $child): ?>
                                                <a href="<?= $Route->url($child['menu_url'], $child['prefix'] ?? null, '') ?>"
                                                    class="rbn-dash-dropdown-link <?= $Route->isActive($child['menu_url'], $child['prefix'] ?? null, '') ? 'active' : '' ?>"
                                                    onclick="window.location.href=this.href; return false;">
                                                    <i class="<?= htmlspecialchars((string) $child['menu_icon']) ?>"></i>
                                                    <span><?= htmlspecialchars((string) $child['menu_title']) ?></span>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="rbn-dash-nav-item">
                                        <a href="<?= $Route->url($menu['menu_url'], $menu['prefix'] ?? null, '') ?>"
                                            class="rbn-dash-nav-link <?= $Route->isActive($menu['menu_url'], $menu['prefix'] ?? null, '') ? 'active' : '' ?>">
                                            <i class="<?= htmlspecialchars($menu['menu_icon']) ?>"></i>
                                            <span><?= htmlspecialchars($menu['menu_title']) ?></span>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div> <!-- END .rbn-dash-sidebar-nav -->

    <script>window.rbnInitSidebar && window.rbnInitSidebar();</script>

    <!-- Admin Sidebar Footer Actions -->
    <div class="rbn-admin-sidebar-footer p-3 mt-auto">
        <div class="mb-3">
            <button class="rbn-admin-theme-toggle w-100 d-flex align-items-center justify-content-center gap-2"
                type="button" data-bs-toggle="offcanvas" data-bs-target="#serverHealthCanvas"
                data-tooltip="Sunucu Durumu">
                <i class="ri-server-line"></i>
                <span class="rbn-dash-sidebar-text">Sunucu Durumu</span>
            </button>
        </div>

        @hasrole(['developer', 'superadmin'])
        <div class="rbn-admin-dev-group d-flex justify-content-between gap-2 pt-2">
            <a href="<?= $Route->url('syshub', 'developer') ?>"
                class="rbn-admin-dev-btn w-100 <?= $Route->isActive('syshub', 'developer') ? 'active' : '' ?>"
                data-tooltip="Syshub">
                <i class="ri-cpu-line"></i>
            </a>
            <a href="<?= $Route->url('webhub', 'developer') ?>"
                class="rbn-admin-dev-btn w-100 <?= $Route->isActive('webhub', 'developer') ? 'active' : '' ?>"
                data-tooltip="Webhub">
                <i class="ri-global-line"></i>
            </a>
            <?php
            $isDeveloper = ($this->handler('access') ?? \Rbn\Framework\Core\Base\Services\BaseService::get()?->handler('access'))?->can('developer');
            $settingsUrl = $isDeveloper ? $Route->url('backstage', 'developer') : $Route->url('settings', 'admin');
            $settingsTitle = $isDeveloper ? 'Backstage' : 'Site Ayarları';
            $isSettingsActive = $isDeveloper ? $Route->isActive('backstage', 'developer') : $Route->isActive('settings', 'admin');
            ?>
            <a href="<?= $settingsUrl ?>" class="rbn-admin-dev-btn w-100 <?= $isSettingsActive ? 'active' : '' ?>"
                data-tooltip="<?= $settingsTitle ?>">
                <i class="ri-settings-4-line"></i>
            </a>
        </div>
        @endhasrole
    </div>
</nav>