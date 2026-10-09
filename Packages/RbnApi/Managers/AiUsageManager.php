<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnApi\Managers;

use Rbn\Framework\Core\Base\Services\BaseManager;
use Rbn\Framework\Core\Base\Attributes\Component;
use Rbn\Framework\Packages\RbnApi\Models\AiData;
use Rbn\Framework\Core\System\Storage\Providers\BootCacheProvider;
use Rbn\Framework\Core\System\Kernel\Base\PreBoot;

/**
 * AiUsageManager - Central AI Usage Telemetry & Cost Manager 📊🤖⚡
 * RBN Framework Framework Standards.
 */
#[Component(alias: 'aiUsage', type: 'manager')]
class AiUsageManager extends BaseManager
{
    /**
     * Gemini/AI API kullanımını master log tablosuna kaydeder 🚀
     */
    public function recordUsage(
        string $model,
        array $usageMetadata,
        ?string $projectKey = null,
        ?string $taskKey = null,
        string $requestType = 'text'
    ): void {
        try {
            $projectKey = trim((string) ($projectKey ?: (project_key() ?: 'master')));
            if (empty($projectKey)) {
                $projectKey = 'master';
            }

            $promptTokens = (int) ($usageMetadata['promptTokenCount'] ?? 0);
            $outputTokens = (int) ($usageMetadata['candidatesTokenCount'] ?? 0);
            $totalTokens = (int) ($usageMetadata['totalTokenCount'] ?? ($promptTokens + $outputTokens));

            // Token harcanmamışsa atla
            if ($totalTokens <= 0 && !str_contains(strtolower($model), 'imagen')) {
                return;
            }

            $costUsd = $this->calculateCost($model, $promptTokens, $outputTokens);

            // 🛡️ Key Type Tespiti (Lokal ortamda 'free' test anahtarı kullanılır, canlıda 'paid')
            // [FW-096-D8] TEK okuyucu: `PreBoot::isProductionDeclared()` (`secrets.php` `app.environment`).
            $isLocal = (function_exists('is_local') && is_local()) || !PreBoot::isProductionDeclared();
            $keyType = $isLocal ? 'free' : 'paid';

            // Common DB (cm_log_ai_usages) tablosuna doğrudan hızlı insert 🗄️⚡
            $this->model('common.aiUsage')->insert([
                'project_key' => $projectKey,
                'task_key' => $taskKey,
                'request_type' => $requestType,
                'ai_model' => $model,
                'prompt_tokens' => $promptTokens,
                'output_tokens' => $outputTokens,
                'total_tokens' => $totalTokens,
                'estimated_cost_usd' => $costUsd,
                'key_type' => $keyType,
                'created_at' => date('Y-m-d H:i:s')
            ]);

        } catch (\Throwable $e) {
            // Log hatası ana akışı engellemesin
            $this->storage->logs()->channel('warning')->warning("[AiUsageManager] Log kaydedilirken hata: " . $e->getMessage());
        }
    }

    /**
     * Kullanılan Modele Göre Birebir Dolar Maliyetini Hesaplar (AiData Hub) 💵🎯
     */
    public function calculateCost(string $model, int $promptTokens, int $outputTokens): float
    {
        $modelClean = strtolower(trim(str_replace(['models/', 'v1beta/'], '', $model)));

        // 1. Birebir Model Eşleşmesi Kontrolü (AiData Hub) 🎯
        $pricing = AiData::getPricing($modelClean);
        if ($pricing !== null) {
            if (isset($pricing['per_image'])) {
                return $pricing['per_image'];
            }
            $inputCost = ($promptTokens / 1000000) * $pricing['input'];
            $outputCost = ($outputTokens / 1000000) * $pricing['output'];
            return round($inputCost + $outputCost, 6);
        }

        // 2. Dinamik Model Ailesi Fallback Kontrolü 🧠
        if (str_contains($modelClean, 'lite') || str_contains($modelClean, '8b')) {
            $inputCost = ($promptTokens / 1000000) * 0.0375;
            $outputCost = ($outputTokens / 1000000) * 0.1500;
        } elseif (str_contains($modelClean, 'pro')) {
            $inputCost = ($promptTokens / 1000000) * 1.2500;
            $outputCost = ($outputTokens / 1000000) * 5.0000;
        } elseif (str_contains($modelClean, 'imagen')) {
            return str_contains($modelClean, 'fast') ? 0.015000 : 0.030000;
        } else {
            // Standart Flash Varsayılan Tarifesi
            $inputCost = ($promptTokens / 1000000) * 0.0750;
            $outputCost = ($outputTokens / 1000000) * 0.3000;
        }

        return round($inputCost + $outputCost, 6);
    }

    /**
     * Güncel Döviz Kurları (Ortak Workspace .cache/global_currency_rates.json) 💵💶🇹🇷
     */
    public function getCurrencyRates(): array
    {
        $cached = BootCacheProvider::get('currency_rates', null, 'global_');

        if ($cached && isset($cached['USD_TRY'], $cached['expires_at']) && time() < (int) $cached['expires_at']) {
            return $cached;
        }

        $rates = [];
        $res = $this->remote->get('https://api.exchangerate-api.com/v4/latest/USD', [], [], ['timeout' => 5]);

        if (!empty($res['status']) && $res['status'] === 'success' && !empty($res['data']['rates'])) {
            $apiRates = $res['data']['rates'];
            if (isset($apiRates['TRY'])) {
                $rates['USD_TRY'] = (float) $apiRates['TRY'];
                if (isset($apiRates['EUR']) && (float) $apiRates['EUR'] > 0) {
                    $rates['EUR_TRY'] = round((float) $apiRates['TRY'] / (float) $apiRates['EUR'], 4);
                }
            }
            $rates['updated_at'] = date('Y-m-d H:i:s');
            $rates['expires_at'] = time() + 86400;

            BootCacheProvider::set('currency_rates', $rates, null, 'global_');
        }

        return $rates;
    }

    /**
     * Filtrelenmiş AI kullanım günlüğünü ve özet istatistikleri döner 📊📜
     */
    public function getFilteredReport(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $currentProjectKey = active_project_key();
        $selectedProject = trim((string) ($filters['project'] ?? $currentProjectKey));
        $selectedTask = trim((string) ($filters['task_key'] ?? ''));
        $selectedModel = trim((string) ($filters['ai_model'] ?? ''));
        $selectedType = trim((string) ($filters['request_type'] ?? ''));
        $selectedSearch = trim((string) ($filters['search'] ?? ''));

        $query = $this->model('common.aiUsage')->withoutProjectScope()->query();

        if (!empty($selectedProject) && $selectedProject !== 'all') {
            $query->where('project_key', '=', $selectedProject);
        }
        if (!empty($selectedTask)) {
            $query->where('task_key', '=', $selectedTask);
        }
        if (!empty($selectedModel)) {
            $query->where('ai_model', '=', $selectedModel);
        }
        if (!empty($selectedType)) {
            $query->where('request_type', '=', $selectedType);
        }
        if (!empty($selectedSearch)) {
            $query->where(function ($q) use ($selectedSearch) {
                $q->where('task_key', 'LIKE', "%{$selectedSearch}%")
                  ->orWhere('ai_model', 'LIKE', "%{$selectedSearch}%");
            });
        }

        $summary = (clone $query)->select('
            COUNT(*) as total_requests,
            COALESCE(SUM(CASE WHEN request_type = "image" THEN 1 ELSE 0 END), 0) as image_requests,
            COALESCE(SUM(CASE WHEN request_type != "image" OR request_type IS NULL THEN 1 ELSE 0 END), 0) as text_requests,
            COALESCE(SUM(estimated_cost_usd), 0) as total_cost_usd,
            COALESCE(SUM(CASE WHEN request_type = "image" THEN estimated_cost_usd ELSE 0 END), 0) as image_cost_usd,
            COALESCE(SUM(CASE WHEN request_type != "image" OR request_type IS NULL THEN estimated_cost_usd ELSE 0 END), 0) as text_cost_usd,
            COALESCE(SUM(total_tokens), 0) as total_tokens,
            COALESCE(SUM(prompt_tokens), 0) as total_prompt_tokens,
            COALESCE(SUM(output_tokens), 0) as total_output_tokens
        ')->first();

        $logsData = $query->orderBy('id', 'DESC')->get();

        // Distinct dropdown options
        $projectRows = $this->model('common.aiUsage')->withoutProjectScope()->query()->select('DISTINCT project_key')->get();
        $allProjects = [];
        foreach ($projectRows as $row) {
            $p = is_object($row) ? ($row->project_key ?? '') : ($row['project_key'] ?? '');
            if (!empty($p)) {
                $allProjects[] = $p;
            }
        }
        if (empty($allProjects)) {
            $allProjects = [$currentProjectKey];
        }

        // Distinct dropdown options scoped to active/selected project
        $taskQuery = $this->model('common.aiUsage')->withoutProjectScope()->query()->select('DISTINCT task_key');
        if (!empty($selectedProject) && $selectedProject !== 'all') {
            $taskQuery->where('project_key', '=', $selectedProject);
        }
        $taskRows = $taskQuery->get();
        $allTasks = [];
        foreach ($taskRows as $row) {
            $t = is_object($row) ? ($row->task_key ?? '') : ($row['task_key'] ?? '');
            if (!empty($t)) {
                $allTasks[] = $t;
            }
        }

        $modelQuery = $this->model('common.aiUsage')->withoutProjectScope()->query()->select('DISTINCT ai_model');
        if (!empty($selectedProject) && $selectedProject !== 'all') {
            $modelQuery->where('project_key', '=', $selectedProject);
        }
        $modelRows = $modelQuery->get();
        $allModels = [];
        foreach ($modelRows as $row) {
            $m = is_object($row) ? ($row->ai_model ?? '') : ($row['ai_model'] ?? '');
            if (!empty($m)) {
                $allModels[] = $m;
            }
        }

        $totalReq = (int) ($summary['total_requests'] ?? 0);
        $totalCostUsd = (float) ($summary['total_cost_usd'] ?? 0);
        $textCostUsd = (float) ($summary['text_cost_usd'] ?? 0);
        $imageCostUsd = (float) ($summary['image_cost_usd'] ?? 0);

        $avgCost = $totalReq > 0 ? ($totalCostUsd / $totalReq) : 0;
        $hasFilter = !empty($selectedTask) || !empty($selectedModel) || !empty($selectedSearch);

        $currencyRates = $this->getCurrencyRates();
        $exchangeRate = (float) ($currencyRates['USD_TRY'] ?? 0);

        $totalCostTry = $totalCostUsd * $exchangeRate;
        $textCostTry = $textCostUsd * $exchangeRate;
        $imageCostTry = $imageCostUsd * $exchangeRate;

        return [
            'summary' => $summary,
            'logs' => $logsData,
            'selectedProject' => $selectedProject,
            'selectedTask' => $selectedTask,
            'selectedModel' => $selectedModel,
            'selectedSearch' => $selectedSearch,
            'selectedType' => $selectedType,
            'hasFilter' => $hasFilter,
            'avgCost' => $avgCost,
            'exchangeRate' => $exchangeRate,
            'totalCostTry' => $totalCostTry,
            'textCostTry' => $textCostTry,
            'imageCostTry' => $imageCostTry,
            'allProjects' => $allProjects,
            'allTasks' => array_filter($allTasks),
            'allModels' => array_filter($allModels),
            'pricingMap' => AiData::getPricingMap()
        ];
    }

    /**
     * AI Loglarını temizler 🗑️
     */
    public function clearLogs(?string $projectKey = null): bool
    {
        $query = $this->model('master.aiUsageLog')->query();
        if (!empty($projectKey) && $projectKey !== 'all') {
            $query->where('project_key', '=', $projectKey);
        }
        return (bool) $query->delete();
    }
}
