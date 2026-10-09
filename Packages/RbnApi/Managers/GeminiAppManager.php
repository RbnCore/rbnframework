<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnApi\Managers;

use Rbn\Framework\Core\Base\Services\BaseManager;

/**
 * GeminiAppManager - RBN Framework Framework Central AI Manager & Orchestrator Base 🧠🛰️⚓
 * 
 * RBN Framework Framework Standards.
 * Centralizes all project_key resolution, module loading, prompt handler resolution,
 * persona merging, API execution, and response formatting for all projects.
 */
abstract class GeminiAppManager extends BaseManager
{
    /**
     * Optional project task mapping 🗺️
     */
    protected array $taskMap = [];

    /**
     * Optional project rules 📜
     */
    protected array $rules = [];

    /**
     * Single Unified AI Text Generation Method 🖋️
     * Handles blog, news, custom, or autonomous task types in one central method across all projects.
     */
    public function generateText(string $typeOrTitle, string|array $titleOrContext = '', array $context = []): array
    {
        [$title, $context, $type] = $this->parseTextArgs($typeOrTitle, $titleOrContext, $context);

        $taskMap = array_merge($this->taskMap, $context['task_map'] ?? []);
        $defaultRules = ['identity', 'writingTone', 'contentQuality', 'seo', 'innerLinking', 'blogStructure'];
        $rules = array_values(array_unique(array_merge($defaultRules, $this->rules, $context['rules'] ?? [])));

        // Otonom görev tipi ve $taskMap tanımlıysa orkestrasyonu otomatik çalıştır 🚀
        if (!empty($context['task_type']) && !empty($taskMap)) {
            return $this->runTaskOrchestration($type, $title, $context, $taskMap, $rules);
        }

        $settingMethod = match ($type) {
            'blog' => 'getBlogTextSettings',
            'news' => 'getNewsTextSettings',
            default => 'getTextSettings'
        };

        $res = $this->resolvePromptModule($context, $settingMethod);
        if (!($res['success'] ?? false)) {
            return $res;
        }

        $context = $res['context'];
        $response = $this->service('api')->gemini($type, $title, $context);

        if (!($response['success'] ?? false)) {
            return [
                'success' => false,
                'message' => 'AI Üretim Hatası: ' . ($response['message'] ?? 'API Hatası')
            ];
        }

        $metaData = $response;
        unset($metaData['success']);

        return [
            'success' => true,
            'data' => $metaData
        ];
    }

    /**
     * Standard AI Image Generation & Server Save 🎨🖼️⚓
     */
    public function generateImage(string $topic, ?string $projectKey = null, bool|string $save = false, array $extraContext = []): array
    {
        $extraContext = $this->prepareContext($extraContext);
        $projectKey = $projectKey ?: ($extraContext['project_key'] ?: (function_exists('project_key') ? project_key() : ''));

        $res = $this->resolvePromptModule(array_merge(['project_key' => $projectKey], $extraContext), 'getImageSettings');
        if (!($res['success'] ?? false)) {
            return $res;
        }

        $context = $res['context'];
        $response = $this->service('api')->gemini('image', $topic, $context);

        if (!($response['success'] ?? false) || empty($response['base64'])) {
            return [
                'success' => false,
                'message' => $response['message'] ?? 'Görsel üretilemedi.'
            ];
        }

        if ($save === false) {
            return [
                'success' => true,
                'base64' => $response['base64'],
                'message' => 'Görsel üretildi.'
            ];
        }

        $type = is_string($save) ? $save : 'blog';
        $targetFolder = "images/{$type}/" . date('Y/m');

        $uploadRes = $this->service('image')->base64Image(
            $response['base64'],
            $targetFolder,
            'public',
            ['extension' => 'webp']
        );

        if ($uploadRes['success'] ?? false) {
            $imagePath = '/' . ltrim($uploadRes['path'] ?? '', '/');
            return [
                'success' => true,
                'url' => $imagePath,
                'image_url' => $imagePath,
                'image' => $imagePath,
                'message' => 'Görsel başarıyla üretildi!'
            ];
        }

        return [
            'success' => false,
            'message' => 'Görsel sunucuya kaydedilemedi: ' . ($uploadRes['message'] ?? 'Hata')
        ];
    }

    /**
     * Centralized Autonomous Task Runner 🚀
     * Handles prompt handler loading, persona merging, schema fetching, and API dispatch.
     */
    protected function runTaskOrchestration(string $type, string $title, array $context, array $taskMap, array $rules = []): array
    {
        $res = $this->resolvePromptModule($context);
        if (!($res['success'] ?? false)) {
            return $res;
        }

        $module = $res['module'];
        $context = $res['context'];
        $taskType = $context['task_type'] ?? '';

        if (!isset($taskMap[$taskType])) {
            return [
                'success' => false,
                'message' => "Geçersiz veya eksik otonom görev tipi (task_type: '" . ($taskType !== '' ? $taskType : 'boş') . "')!"
            ];
        }

        $target = $taskMap[$taskType];
        $handler = $this->prompt($target['prompt']);
        $method = $target['method'];

        $projectHandler = $res['prompt'] ?? null;

        $basePersona = $handler->$method($context);
        $projectPersona = ($projectHandler && method_exists($projectHandler, $method)) ? $projectHandler->$method($context) : [];
        $persona = $handler->mergeSettings($basePersona, $projectPersona);

        $taskMethod = str_replace('TextSettings', 'Task', $method);
        $task = ($projectHandler && method_exists($projectHandler, $taskMethod))
            ? $projectHandler->$taskMethod($title, $context)
            : (($projectHandler && method_exists($projectHandler, 'getTask'))
                ? $projectHandler->getTask($title, $context)
                : $handler->getContentText($title, $context));
        $schemaMethod = str_replace('TextSettings', 'Schema', $method);
        $schema = ($projectHandler && method_exists($projectHandler, $schemaMethod))
            ? $projectHandler->$schemaMethod($context)
            : (($projectHandler && method_exists($projectHandler, 'getSchema'))
                ? $projectHandler->getSchema($context)
                : (method_exists($handler, 'getSchema') ? $handler->getSchema($context) : null));

        $options = $context;

        if ($schema) {
            $options['schema'] = $schema;
        }

        if (!empty($rules)) {
            $options['rules'] = $rules;
        } elseif (method_exists($handler, 'getRules')) {
            $options['rules'] = $handler->getRules($context);
        }

        if ($persona) {
            $options['persona'] = $persona;
        }

        unset($options['task_type'], $options['task_map']);

        return $this->generateText($type, $task, $options);
    }

    /**
     * Parses 2-arg or 3-arg generateText parameters 🧭
     */
    protected function parseTextArgs(string $typeOrTitle, string|array $titleOrContext = '', array $context = []): array
    {
        if (is_array($titleOrContext)) {
            $context = $titleOrContext;
            $title = $typeOrTitle;
            $type = $context['task_type'] ?? 'custom';
        } else {
            $title = (string) $titleOrContext;
            $type = $typeOrTitle;
        }

        return [$title, $context, $type];
    }

    /**
     * Enforces mandatory project_key and task_key in context 🛡️
     */
    protected function prepareContext(array $context): array
    {
        if (empty($context['project_key'])) {
            $context['project_key'] = function_exists('project_key') ? project_key() : '';
        }

        if (empty($context['task_key'])) {
            $context['task_key'] = 'manuel';
        }

        return $context;
    }

    /**
     * Centralized module resolution and prompt persona resolver 🎯
     * Automatically loads and returns the instantiated prompt handler object.
     */
    protected function resolvePromptModule(array $context, ?string $method = null): array
    {
        $context = $this->prepareContext($context);
        $projectKey = $context['project_key'] ?? '';
        $taskType = $context['task_type'] ?? '';

        // 1. Doğrudan context'te tanımlı prompt veya module
        $module = $context['prompt'] ?? ($context['module'] ?? null);

        // 2. GeminiAppService içindeki $taskMap üzerinden o göreve ait prompt tanımı 🎯
        if (empty($module) && !empty($taskType) && !empty($context['task_map'][$taskType]['prompt'])) {
            $module = $context['task_map'][$taskType]['prompt'];
        }

        // 3. $taskMap içindeki ilk tanımlı prompt (Projenin ana prompt alias'ı)
        if (empty($module) && !empty($context['task_map'])) {
            $firstTask = reset($context['task_map']);
            if (!empty($firstTask['prompt'])) {
                $module = $firstTask['prompt'];
            }
        }

        // 4. local_model (örn: 'yzg.news') veya model aliasından modül kökü (örn: 'yzg') 🛡️
        if (empty($module) && !empty($context['local_model'])) {
            $parts = explode('.', (string) $context['local_model']);
            $module = $parts[0] ?? '';
        }

        // 5. Routemap veya ProjectKey Fallback
        if (empty($module)) {
            $module = $this->getRouteConfig($projectKey, 'module') ?: $projectKey;
        }

        if (empty($module)) {
            return [
                'success' => false,
                'message' => "Proje '{$projectKey}' için modül yapılandırması bulunamadı."
            ];
        }

        $promptObj = null;
        try {
            $promptObj = $this->prompt($module);
            if (!empty($method) && method_exists($promptObj, $method)) {
                $context['persona'] = $promptObj->$method($context);
            }
        } catch (\Throwable $e) {
            // Optional prompt handler error handling
        }

        return [
            'success' => true,
            'module' => $module,
            'prompt' => $promptObj,
            'context' => $context
        ];
    }
}
