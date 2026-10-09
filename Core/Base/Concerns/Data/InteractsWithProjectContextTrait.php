<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Concerns\Data;

use Rbn\Framework\Core\System\Kernel\Bootstrap;
use Rbn\Framework\Core\Services\Console\Managers\CliManager;
use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * InteractsWithProjectContextTrait - Autonomous Project Context Swapper 🛰️🔄⚓
 * 
 * RBN Framework: Central authority for switching active application context
 * across Web (Admin Panel/Multi-tenant) and CLI (Cron/Queue) environments.
 */
trait InteractsWithProjectContextTrait
{
    use ResolvesProjectConfigTrait;

    /**
     * Aktif çalışan projenin anahtar bilgisini döner 🔑
     */
    public function activeProjectKey(): ?string
    {
        return Bootstrap::getAppContext('project_key') ?: (function_exists('active_project_key') ? active_project_key() : null);
    }

    /**
     * Proje anahtarının geçerli ve yetkili olup olmadığını doğrular 🛡️
     */
    public function isValidProject(string $projectKey): bool
    {
        $projectKey = trim($projectKey);
        if (empty($projectKey) || $projectKey === 'master') {
            return false;
        }

        $groupProjects = function_exists('group_projects') ? group_projects() : [];
        if (!empty($groupProjects)) {
            foreach ($groupProjects as $proj) {
                if (($proj['project_key'] ?? '') === $projectKey) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Anlık proje bağlamını (context) belirtilen projeye taşır ve oturumda mühürler 🔄⚓
     */
    public function switchProjectContext(string $projectKey): bool
    {
        $switched = CliManager::switchProject($projectKey);

        // Web ortamı için oturum mühürleme 🔒
        if ($switched && session_status() === PHP_SESSION_ACTIVE) {
            $storage = BaseService::get()?->service('storage');
            if ($storage && method_exists($storage, 'sessions')) {
                $storage->sessions()->set('active_project_key', $projectKey);
            }
        }

        return $switched;
    }

    /**
     * Belirtilen projenin bağlamında geçici bir işlem çalıştırır ve eski projeye geri döner ⏳
     */
    public function runInProjectContext(string $projectKey, callable $callback): mixed
    {
        return CliManager::runInProject($projectKey, function () use ($projectKey, $callback) {
            return $callback($projectKey, $this);
        });
    }

    /**
     * Proje bağlamını başlangıçtaki varsayılan projeye geri döndürür 🔙
     */
    public function restoreProjectContext(): bool
    {
        return CliManager::restore();
    }
}
