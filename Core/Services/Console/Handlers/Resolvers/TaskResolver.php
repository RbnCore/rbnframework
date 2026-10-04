<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Console\Handlers\Resolvers;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * TaskResolver - Görev (Task) & Builder Çözümleme ve Doğrulama Bileşeni 🎯🔍
 * 
 * Sadece Task ve Builder evrenine ait çözümleme işlerini yapar:
 * 1. Görev sınıfının (task_class) ve fiziki PHP dosyasının varlığını doğrulama
 * 2. Projeye özel Task Builder sınıfını (veya varsayılan builder'ı) çözme
 * 3. DB'de gün/saat boşsa Task sınıfından koddaki varsayılanları (default_days / default_hours) okuma
 * 4. Güvenli Task nesnesi türetme
 */
class TaskResolver extends BaseComponent
{
    /**
     * Görev sınıfının (task_class) ve fiziki PHP dosyasının var olup olmadığını doğrular.
     */
    public function resolveTaskFileExists(string $taskClass, string $projectKey = ''): bool
    {
        if (empty($taskClass)) {
            return false;
        }

        if (class_exists($taskClass)) {
            return true;
        }

        $className = basename(str_replace('\\', '/', $taskClass));
        $filePath = $this->resolveProjectPath($projectKey, "Core/Tasks/{$className}.php");

        if (file_exists($filePath)) {
            require_once $filePath;
            return class_exists($taskClass);
        }

        // Discovery Engine üzerinden sınıf varlığını doğrula
        return $this->task($taskClass) !== null;
    }

    /**
     * Projeye özel Task Builder sınıfını (Örn: BaseActorBuilder) veya varsayılan builder'ı çözer 🚀
     */
    public function resolveProjectBuilder(string $builderName, string $projectKey = ''): ?object
    {
        $customBuilderName = ucfirst($builderName);
        if (!str_ends_with($customBuilderName, 'Builder')) {
            $customBuilderName .= 'Builder';
        }

        $projectBuilderClass = $this->resolveProjectNamespace($projectKey, "Core\\Tasks\\Builders\\{$customBuilderName}");
        $fallbackClass = "Rbn\\Project\\Core\\Tasks\\Builders\\{$customBuilderName}";

        if (!class_exists($projectBuilderClass) && !class_exists($fallbackClass)) {
            $builderFile = $this->resolveProjectPath($projectKey, "Core/Tasks/Builders/{$customBuilderName}.php");
            if (file_exists($builderFile)) {
                require_once $builderFile;
            }
        }

        if (class_exists($projectBuilderClass)) {
            return new $projectBuilderClass();
        }

        if (class_exists($fallbackClass)) {
            return new $fallbackClass();
        }

        return $this->builder($builderName) ?: $this->builder('task.content');
    }

    /**
     * DB'de gün/saat boşsa Task sınıfından default_days ve default_hours verilerini çözümler 🔍
     */
    public function getTaskClassDefaults(array $job): array
    {
        $taskClass = $job['task_class'] ?? '';
        if (empty($taskClass) || !class_exists($taskClass)) {
            return [];
        }

        try {
            $taskKey = $job['task_key'] ?? '';
            $methodName = 'run' . str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $taskKey))) . 'Task';

            $refClass = new \ReflectionClass($taskClass);
            if ($refClass->hasMethod($methodName)) {
                $ref = $refClass->getMethod($methodName);
                $contents = file_get_contents($ref->getFileName());
                if ($contents) {
                    $startLine = $ref->getStartLine();
                    $endLine = $ref->getEndLine();
                    $lines = array_slice(explode("\n", $contents), $startLine - 1, $endLine - $startLine + 1);
                    $code = implode("\n", $lines);

                    $defaults = [];
                    if (preg_match("/'default_days'\s*=>\s*(\[[^\]]+\])/", $code, $mDays)) {
                        $defaults['default_days'] = eval ("return {$mDays[1]};");
                    }
                    if (preg_match("/'default_hours'\s*=>\s*(\[[^\]]+\])/", $code, $mHours)) {
                        $defaults['default_hours'] = eval ("return {$mHours[1]};");
                    }
                    return $defaults;
                }
            }
        } catch (\Throwable $e) {
            return [];
        }

        return [];
    }

    /**
     * Güvenli şekilde Task nesnesi türetir 🧩
     * 
     * @return \Rbn\Framework\Core\Services\Console\Base\AbstractCronTask|object|null
     */
    public function resolveTaskInstance(string $taskClass, string $projectKey = ''): ?object
    {
        if (!$this->resolveTaskFileExists($taskClass, $projectKey)) {
            return null;
        }

        return class_exists($taskClass) ? new $taskClass() : null;
    }
}
