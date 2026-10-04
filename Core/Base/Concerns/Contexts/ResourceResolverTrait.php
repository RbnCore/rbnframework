<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Concerns\Contexts;

/**
 * ResourceResolverTrait - Universal Dynamic Component & Context Resolver 🧬🛰️⚓
 * 
 * RBN 3.5 Masterpiece Standard.
 * Provides zero-code dynamic resolution for repositories, models, and project keys
 * across ALL components (Controllers, Services, Repositories, Providers, Helpers).
 */
trait ResourceResolverTrait
{
    /**
     * Hedef Repository Nesnesini Otonom Çözer 🏛️
     */
    protected function resolveTargetRepository(?string $fallbackKey = null): ?object
    {
        $repoKey = property_exists($this, 'targetRepository') ? $this->targetRepository : $fallbackKey;
        if (!empty($repoKey) && method_exists($this, 'repository')) {
            return $this->repository((string) $repoKey);
        }
        return null;
    }

    /**
     * Hedef Model Nesnesini Otonom Çözer 🏺
     */
    protected function resolveTargetModel(?string $fallbackKey = null): ?object
    {
        $modelKey = property_exists($this, 'targetModel') ? $this->targetModel : $fallbackKey;
        if (!empty($modelKey) && method_exists($this, 'model')) {
            return $this->model((string) $modelKey);
        }
        return null;
    }

    /**
     * Aktif Proje Anahtarını (project_key) Otonom Çözer 🔑
     */
    protected function resolveCurrentProjectKey(?string $passedKey = null): string
    {
        if (!empty($passedKey)) {
            return (string) $passedKey;
        }
        if (property_exists($this, 'projectKey') && !empty($this->projectKey)) {
            return (string) $this->projectKey;
        }
        if (method_exists($this, 'resolveProjectData')) {
            return (string) ($this->resolveProjectData('project_key') ?: '');
        }
        return '';
    }

    /**
     * Güvenli ve standart HTTP 301 yönlendirmesi icra eder 🚀
     */
    protected function perform301Redirect(string $pathOrUrl): void
    {
        $targetUrl = (str_starts_with($pathOrUrl, 'http://') || str_starts_with($pathOrUrl, 'https://'))
            ? $pathOrUrl
            : (function_exists('url') ? url($pathOrUrl) : $pathOrUrl);

        header("Location: " . $targetUrl, true, 301);
        exit;
    }
}
