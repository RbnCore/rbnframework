<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Registries\RegistryMap;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\System\Config\Config;
use Rbn\Framework\Core\System\Paths\Paths;
use Rbn\Framework\Core\System\Registries\RbnSystemInfo;

/**
 * SystemAccessMapTrait - The Entry Gates of the Framework 🏹🏷️⚓
 * 
 * RBN 3.5 Masterpiece: Centralized authority for Aliases and CLI Commands.
 */
trait SystemAccessMapTrait
{
    /**
     * Map of Access & Entry Points 🛰️
     */
    protected function accessMap(): array
    {
        return [
            /* --- Core Infrastructure Aliases 🏷️ --- */
            'aliases' => [
                BaseService::class   => ['BaseService', 'Service'],
                Config::class        => ['Config'],
                Paths::class         => ['Paths'],
                RbnSystemInfo::class => ['RbnSysInfo'],
            ],

            /* --- CLI Command Handlers 🏹 --- */
            'commands' => [
                'db'      => 'Core\Services\Console\Handlers\DatabaseHandlers',
                'make'    => 'Core\Services\Console\Handlers\GeneratorHandlers',
                'migrate' => 'Core\Services\Console\Handlers\MigrationHandlers',
                'system'  => 'Core\Services\Console\Handlers\SystemHandlers',
                // [FW-ALTYAPI-2/H] Kiracı izolasyonu: `tenant:audit` (yalnız
                // okur) + hedef veritabanı başına ekleyici kolon migration'ı.
                'tenant'  => 'Core\Services\Console\Handlers\TenantHandlers',
            ],
        ];
    }
}
