<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Controllers;

use Rbn\Framework\Core\Base\Attributes\SubModule;

/**
 * BotSettingsController - Dedicated Bot & API Settings Manager 🤖🛰️⚓
 */
#[SubModule(
    entity: 'settingsApi',
    service: 'settingsApi'
)]
class BotSettingsController extends RbnAdminController
{
    /** @var string Explicit modal view path override */
    protected ?string $modalView = 'Setting/Partials/modal';

    /**
     * Display the Bot Dashboard 🤖
     */
    public function index(): void
    {
        $options = $this->service->getOptions();
        $projectKey = active_project_key();

        $cronRepo = $this->repository('master.cronJob');
        $cronJobs = $cronRepo ? $cronRepo->getJobsByProject($projectKey) : [];
        $cronScheduleData = $cronRepo ? $cronRepo->getScheduleSummaryData($projectKey) : [];
        $aiReport = $this->manager('aiUsage')->getFilteredReport(['project' => $projectKey]);

        $this->render('Setting/botdash', [
            'options' => $options,
            'cronJobs' => $cronJobs,
            'cronScheduleData' => $cronScheduleData,
            'aiReport' => $aiReport
        ]);
    }

    /**
     * Display dedicated API Keys Settings page 🗝️
     */
    public function apis(): void
    {
        $options = $this->service->getOptions();
        $socialPlatforms = $this->service('socialmedia')->allSocialPlatforms();

        $botOptions = array_filter($options, function ($opt) {
            return in_array($opt['group_key'], ['bot', 'api']) && !str_contains(strtolower($opt['option_key']), 'cron');
        });

        foreach ($botOptions as &$opt) {
            $keyLower = strtolower($opt['option_key']);
            $icon = 'bi-key-fill';

            foreach ($socialPlatforms as $platformKey => $platform) {
                if (str_contains($keyLower, $platformKey)) {
                    $icon = $platform['bi_icon'] ?? 'bi-key-fill';
                    break;
                }
            }

            if ($icon === 'bi-key-fill') {
                if (str_contains($keyLower, 'gemini')) {
                    $icon = 'bi-robot';
                }
            }

            $opt['icon'] = $icon;
        }
        unset($opt);

        $this->render('Setting/apis', [
            'options' => $botOptions
        ]);
    }

    /**
     * Display real Bot Tasks List from Database 🤖
     */
    public function tasks(): void
    {
        $projectKey = active_project_key();
        $cronRepo = $this->repository('master.cronJob');
        $cronJobs = $cronRepo ? $cronRepo->getJobsByProject($projectKey) : [];

        if (empty($cronJobs)) {
            $cronRepo = $this->repository('master.cronJob');
            $cronJobs = $cronRepo ? $cronRepo->getAllJobs() : [];
            foreach ($cronJobs as &$job) {
                $params = is_array($job['params']) ? $job['params'] : json_decode($job['params'] ?? '{}', true);
                $hours = $params['hour'] ?? [];
                $job['hours_string'] = is_array($hours) ? implode(', ', $hours) : (string) $hours;
            }
            unset($job);
        }

        $cronRepo = $this->repository('master.cronJob');
        $cronScheduleData = $cronRepo ? $cronRepo->getScheduleSummaryData($projectKey) : [];

        $this->render('Setting/tasks', [
            'tasks' => $cronJobs,
            'cronScheduleData' => $cronScheduleData
        ]);
    }



    /**
     * Save all bot/api and cron settings
     */
    public function save(): void
    {
        $inputs = $this->request->form([
            'settings' => 'nullable|array',
            'cron' => 'nullable|array'
        ]);

        $success = true;

        // 1. Update API Options
        if (!empty($inputs['settings'])) {
            $options = $this->service->getOptions();
            foreach ($inputs['settings'] as $key => $value) {
                $targetOpt = null;
                foreach ($options as $opt) {
                    if ($opt['option_key'] === $key) {
                        $targetOpt = $opt;
                        break;
                    }
                }

                if ($targetOpt && in_array($targetOpt['group_key'], ['bot', 'api'])) {
                    $result = $this->service->saveOption([
                        'option_key' => $key,
                        'option_value' => $value,
                        'label_tr' => $targetOpt['label_tr'],
                        'group_key' => $targetOpt['group_key']
                    ]);

                    if (!$result) {
                        $success = false;
                    }
                }
            }
        }

        // 2. Update Cron Jobs in rbn_master via Service
        if (!empty($inputs['cron'])) {
            foreach ($inputs['cron'] as $id => $cronData) {
                $result = $this->service('cron')->updateJob((int) $id, $cronData);
                if (!$result) {
                    $success = false;
                }
            }
        }

        // 3. Clear Project & Domain cache files to trigger regeneration with new API Keys
        if ($success) {
            $projectKey = active_project_key();
            \Rbn\Framework\Core\System\Storage\Providers\BootCacheProvider::clearProjectCache($projectKey);
        }

        $this->handleResult($success, 'Bot ve zamanlayıcı ayarları', '/bot-settings');
    }


    public function modal($id = null, ?string $view = null): void
    {
        $type = $this->request->input('type', 'add');

        if ($type === 'pricing') {
            $this->render('Setting/Partials/pricing_modal', [
                'pricingMap' => \Rbn\Framework\Packages\RbnApi\Models\AiData::getPricingMap(),
                'ajax' => true
            ]);
            return;
        }

        $this->render('Setting/Partials/modal', [
            'apiKeys' => $this->manager('api')->getFlattenedKeys(),
            'ajax' => true
        ]);
    }

    /**
     * Create a new API Key/Option
     */
    public function create(): void
    {
        $optionKey = $this->request->input('option_key');
        $optionValue = $this->request->input('option_value');
        $groupKey = $this->request->input('group_key') ?: 'api';

        if (empty($optionKey)) {
            $this->handleResult(false, 'Gerekli alanlar eksik', '/bot-settings');
            return;
        }

        $labelTr = $this->manager('api')->getLabelForKey($optionKey);

        $result = $this->service->saveOption([
            'label_tr' => $labelTr,
            'option_key' => $optionKey,
            'option_value' => $optionValue,
            'group_key' => $groupKey
        ]);

        if ($result) {
            $projectKey = active_project_key();
            \Rbn\Framework\Core\System\Storage\Providers\BootCacheProvider::clearProjectCache($projectKey);
        }

        $this->handleResult($result, 'Yeni API anahtarı', '/bot-settings');
    }

    /**
     * Override status to clear cache on active/passive toggles 🕰️
     */
    public function status(): void
    {
        parent::status();

        $projectKey = active_project_key();
        \Rbn\Framework\Core\System\Storage\Providers\BootCacheProvider::clearProjectCache($projectKey);
    }

    /**
     * Display AI Usage telemetry dashboard with cost breakdown and detailed log table.
     */
    public function aiUsage(): void
    {
        $filters = [
            'project' => $this->request->input('project', active_project_key()),
            'task_key' => $this->request->input('task_key', ''),
            'ai_model' => $this->request->input('ai_model', ''),
            'request_type' => $this->request->input('request_type', ''),
            'search' => $this->request->query('search', '')
        ];

        $report = $this->manager('aiUsage')->getFilteredReport($filters);
        $pager = $this->paginate($report['logs'], 20);

        $this->render('Setting/ai_usage', array_merge($report, [
            'pager' => $pager
        ]));
    }

    /**
     * Clear AI Usage Logs for target project
     */
    public function clearAiUsage(): void
    {
        $projectKey = trim((string) ($this->request->input('project') ?: active_project_key()));
        $result = $this->manager('aiUsage')->clearLogs($projectKey);

        $this->handleResult($result, strtoupper($projectKey) . ' projesine ait AI kullanım logları', '/bot-settings/ai-usage');
    }
}
