<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Gatekeepers;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\Support\Contracts\Base\BaseServiceInterface;
use Rbn\Framework\Core\System\Config\Engine\Database\DatabaseConfig;
/**
 * DatabaseGuardService - The Administrative Database Chef 🛡️👨‍🍳⚓
 * 
 * RBN 3.5: Central authority for database path resolution and integrity validation.
 * No one connects to the database without asking the Chef for path clearance.
 * 
 * [SYMMETRIC LAZY DISCOVERY] 🏛️🛰️✨
 * 
 * @property-read \Rbn\Framework\Core\Services\Gatekeepers\Handlers\DatabaseGuardHandler $databaseGuardHandler
 */
class DatabaseGuardService extends BaseService implements BaseServiceInterface
{
    /** @var \Rbn\Framework\Core\Services\Gatekeepers\Handlers\DatabaseGuardHandler */
    protected $databaseGuardHandler;

    /**
     * Boot the Guard ⚓
     * RBN 3.5: Clean boot to prevent circular discovery loops during bootstrap. 🧘‍♂️🧬
     */
    public function boot(): void
    {
        // Discovery is deferred until first call (Lazy-Loading) 😴🛰️
    }

    /**
     * Strategic Entry Point: Resolve and Validate Database Path 🛰️
     */
    public function resolvePath(string $category, ?string $projectKey = null): string
    {
        // 🎼 RBN 3.5: Masterpiece Lazy-Loading 🎻⚓
        if ($this->databaseGuardHandler === null) {
            $this->databaseGuardHandler = $this->handler('databaseGuard');
        }

        return $this->databaseGuardHandler->resolve($category, $projectKey);
    }

    /**
     * Ultimate Entry Point: Resolve, Validate and Return complete DatabaseConfig DTO 🏺⚖️🛡️⚓
     * 
     * [WORKFLOW]
     * 1. Resolve & Validate path.
     * 2. Load and verify raw credentials.
     * 3. Transform raw data into a validated DatabaseConfig object.
     */
    public function resolveConfig(string $category, ?string $projectKey = null): DatabaseConfig
    {
        // 1. Resolve Path (Triggers Integrity Checks via Handler) 🩺🛡️
        $path = $this->resolvePath($category, $projectKey);

        // 2. Return complete DTO via the specialized Config Factory 🏺⚖️⚓
        // This automatically handles Master Identity bypass or Project file loading.
        return DatabaseConfig::fromFile($path, $category);
    }

    /**
     * Full System Check (Pre-flight Diagnostic) 🩺🚦
     * Used by DatabaseDoctor to ensure all configured databases are ready.
     */
    public function check(): void
    {
        // 1. Validate Master (The Identity Registry) 🏛️
        $this->resolvePath('database_master');

        // 2. Validate Project (The Active Application) 🏠
        $this->resolvePath('database_project');
    }
}
