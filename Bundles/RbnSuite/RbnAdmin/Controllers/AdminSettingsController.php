<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Controllers;

use Rbn\Framework\Core\Base\Attributes\SubModule;

/**
 * AdminSettingsController - Centralized Administration Settings Hub ⚙️🛰️⚓
 */
#[SubModule(
    entity: 'setting',
    service: 'settings'
)]
class AdminSettingsController extends RbnAdminController
{
    public function index(?string $type = null): void
    {
        // 1. Fetch all admin settings to find active groups (Auto-resolves active project via Trait)
        $allSettings = $this->service->withRole('admin')->all();
        $activeGroupIds = [];
        foreach ($allSettings as $setting) {
            $groupId = is_object($setting) ? ($setting->group_id ?? null) : ($setting['group_id'] ?? null);
            if ($groupId) {
                $activeGroupIds[$groupId] = true;
            }
        }

        // 2. Fetch groups and filter only those that contain active admin settings
        $allGroups = $this->service->groups()->all();
        $groups = [];
        foreach ($allGroups as $group) {
            $groupId = is_object($group) ? ($group->id ?? null) : ($group['id'] ?? null);
            $groupKey = is_object($group) ? ($group->group_key ?? '') : ($group['group_key'] ?? '');
            if (isset($activeGroupIds[$groupId]) && $groupKey !== 'appearance') {
                $groups[] = $group;
            }
        }

        // 3. Resolve active group type
        if (!$type && !empty($groups)) {
            $type = is_object($groups[0]) ? ($groups[0]->group_key ?? null) : ($groups[0]['group_key'] ?? null);
        }

        // 4. Fetch settings for the active group using SettingsService
        $settings = $type ? $this->service->withGroup($type)->withRole('admin')->decorate(true, true)->all() : [];

        $activeGroup = null;
        if ($type) {
            foreach ($groups as $group) {
                if (($group['group_key'] ?? '') === $type) {
                    $activeGroup = $group;
                    break;
                }
            }
        }

        // Calculate badge counts (in-memory, no extra DB query)
        $groupKeyMap = [];
        foreach ($groups as $group) {
            $gId = is_object($group) ? ($group['id'] ?? null) : ($group['id'] ?? null);
            $gKey = is_object($group) ? ($group['group_key'] ?? '') : ($group['group_key'] ?? '');
            if ($gId && $gKey) {
                $groupKeyMap[$gId] = $gKey;
            }
        }

        $badgeCounts = [];
        foreach ($allSettings as $setting) {
            $groupId = is_object($setting) ? ($setting->group_id ?? null) : ($setting['group_id'] ?? null);
            if ($groupId && isset($groupKeyMap[$groupId])) {
                $gKey = $groupKeyMap[$groupId];
                $badgeCounts[$gKey] = ($badgeCounts[$gKey] ?? 0) + 1;
            }
        }

        $this->render('Setting/index', [
            'groups' => $groups,
            'settings' => $settings,
            'activeGroup' => $activeGroup,
            'currentType' => $type,
            'badgeCounts' => $badgeCounts
        ]);
    }

    public function update(): void
    {
        $data = $this->request->form([
            'settings' => 'required|array',
            'type' => 'required',
            'project' => 'nullable'
        ]);

        $projectKey = $data['project'] ?? 'default';

        // Perform setting updates via SettingsService
        $result = $this->service->updateSettings($data['settings'], $projectKey);

        $redirectUrl = '/settings/' . $data['type'];
        $this->handleResult($result, 'Sistem ayarları', $redirectUrl);
    }
}
