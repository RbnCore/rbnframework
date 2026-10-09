<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\System;

use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * SettingsService - Universal Application Configuration Manager 🛰️⚙️⚓
 * 
 * RBN Framework: [CENTRALIZED ORCHESTRATION] 🏛️✨
 * 
 * @property \Rbn\Framework\Core\Database\Repositories\Project\SettingsRepository $projectSettingsRepository
 * @property \Rbn\Framework\Core\Services\System\Handlers\SettingsHandler $SettingsHandler
 * 
 * Minimalist service orchestration. Relying on Provider-Aware Base Traits 
 * for 90% of operations. Custom logic is kept as lean strategic bridges.
 */
class SettingsService extends BaseService
{
    /** --- Infrastructure DNA --- */
    protected $targetModel = 'project.settings';
    protected array $cacheKeys = ['settings_all', 'settings_shield'];

    /** @var bool Loop Sentinel 🛡️ */
    private bool $isBusy = false;

    /** @var array<int, string> Group map static memory cache 🧠⚡ */
    private static array $groupMapCache = [];

    /**
     * Boot: Initialize Service Components (Autonomous Discovery) 🛰️🎯
     */
    public function boot(): void
    {
        // 🎼 RBN Framework: [PURE MAGIC ALIGNMENT] 🪄✨
        // Handlers and Providers are now discovered autonomously via docblocks.
        // No explicit assignments needed if keys match.
    }

    /* ==========================================================================
       [ FLUENT STRATEGIES ] - Populating the Zero-Code Criteria Pool 📦🛰️
       ========================================================================== */

    public function withGroup(string $key): self
    {
        $this->criteria['group_key'] = $key;
        return $this;
    }
    public function withProject(string $projectKey): self
    {
        $this->criteria['project_key'] = $projectKey;
        return $this;
    }
    public function withRole(string $role): self
    {
        $this->criteria['role'] = $role;
        return $this;
    }
    public function withKey(string $key): self
    {
        $this->criteria['setting_key'] = $key;
        return $this;
    }

    /**
     * Toggle Decoration (Presentation Layer Bridge) 🎭
     */
    public function decorate(bool $status = true, bool $select = true): self
    {
        $this->criteria['decorate_status'] = $status;
        $this->criteria['decorate_select'] = $select;
        return $this;
    }

    /**
     * Switch Target Context 🏺
     */
    public function groups(): self
    {
        $this->criteria['target'] = 'groups';
        return $this;
    }

    /* ==========================================================================
       [ ORCHESTRATION OVERRIDES ] - Fine-tuning the Symphony 🎻✨
       ========================================================================== */

    /**
     * Get All (Extending Base with Decoration) 🎻🎨
     */
    public function all(): array
    {
        // Otonom Proje Bağlamı Entegrasyonu 🚀
        if (empty($this->criteria['project_key'])) {
            $activeKey = $this->activeProjectKey();
            if (!empty($activeKey)) {
                $this->criteria['project_key'] = $activeKey;
            }
        }

        // 1. RBN Framework: [STRATEGIC ORCHESTRATION] 🛰️✨
        $results = $this->repository('project.settings')->fetch($this->criteria);

        // 2. Decorative Finish 🎭
        if ($this->criteria['decorate_select'] ?? false)
            $this->SettingsHandler->decorateSelectOptions($results);
        if ($this->criteria['decorate_status'] ?? false)
            $this->SettingsHandler->decorateStatusBadges($results);

        $this->criteria = []; // Reset pool
        return $results;
    }

    /**
     * Strategic Read helper (Frontend Friendly) 🕊️🏛️⚓
     * 
     * RBN Framework: [SINGLE TABLE CACHE ARCHITECTURE]
     * Caches all active settings in a single master cache file ('settings_all.cache') 
     * instead of spawning multiple separate group cache files.
     */
    public function read(?string $groupKey = null): array
    {
        // 🎼 RBN Framework: [RECURSION GUARD] 🛡️
        if ($this->isBusy) {
            return [];
        }

        $this->isBusy = true;

        try {
            $projectKey = $this->activeProjectKey();
            if (empty($projectKey) || $projectKey === 'default' || $projectKey === 'master') {
                return [];
            }

            $cached = $this->cache()->remember("settings_all", function () use ($projectKey) {
                if (empty(self::$groupMapCache)) {
                    $rawGroups = $this->repository('project.settings')->fetch(['target' => 'groups', 'is_active' => 1]);
                    foreach ($rawGroups as $g) {
                        $id = is_array($g) ? ($g['id'] ?? null) : ($g->id ?? null);
                        $key = is_array($g) ? ($g['group_key'] ?? null) : ($g->group_key ?? null);
                        if ($id !== null && $key !== null) {
                            self::$groupMapCache[(int)$id] = (string)$key;
                            self::$groupMapCache[(string)$id] = (string)$key;
                        }
                    }
                }
                $groupMap = self::$groupMapCache;

                $results = $this->repository('project.settings')->fetch([
                    'project_key' => $projectKey,
                    'is_active' => 1
                ]);

                $mapped = [];
                foreach ($results as $m) {
                    $gId = is_array($m) ? ($m['group_id'] ?? null) : ($m->group_id ?? null);
                    $gKey = is_array($m) ? ($m['group_key'] ?? null) : ($m->group_key ?? null);
                    if (empty($gKey) && $gId !== null) {
                        $gKey = $groupMap[$gId] ?? ($groupMap[(int)$gId] ?? '');
                    }
                    $sKey = is_array($m) ? ($m['setting_key'] ?? '') : ($m->setting_key ?? '');
                    $sValue = is_array($m) ? ($m['setting_value'] ?? null) : ($m->setting_value ?? null);

                    if (!empty($gKey) && !empty($sKey)) {
                        $mapped[$gKey][$sKey] = $sValue;
                    }
                }

                return $mapped;
            });

            $allSettings = is_array($cached) ? $cached : [];

            if ($groupKey) {
                return $allSettings[$groupKey] ?? [];
            }

            return $allSettings;
        } finally {
            $this->isBusy = false;
        }
    }

    /**
     * Update settings for a specific project and clear cache ⚙️🛰️
     */
    public function updateSettings(array $settings, string $projectKey): bool
    {
        $result = $this->repository('project.settings')->updateSettings($settings, $projectKey);
        if ($result) {
            $this->clearCache(null, $projectKey);
        }
        return $result;
    }

    /**
     * Overrides bulkValueUpdate to support high-performance project settings updates.
     */
    public function bulkValueUpdate(array $data): self
    {
        $projectKey = active_project_key() ?: 'default';
        $this->lastResult = $this->updateSettings($data, $projectKey);
        return $this;
    }
}
