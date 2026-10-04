<?php

namespace Rbn\Framework\Core\System\Storage\Providers;

use Rbn\Framework\Core\System\Storage\Base\BaseStorageProvider;
use Rbn\Framework\Core\System\Paths\Paths;

/**
 * BackupProvider - Sistem Yedekleme Klasörü Yönetimi 📦
 */
class BackupProvider extends BaseStorageProvider
{
    protected string $storageName = 'backups';
    protected bool $encrypted = false;
    protected string $format = 'raw';

    protected function getStorageDir(): string
    {
        $projectKey = $this->projectKey ?: project_key() ?: 'default';
        return Paths::project()->storage('backups/' . $projectKey);
    }
}
