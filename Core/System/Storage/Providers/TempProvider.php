<?php

namespace Rbn\Framework\Core\System\Storage\Providers;

use Rbn\Framework\Core\System\Storage\Base\BaseStorageProvider;
use Rbn\Framework\Core\System\Paths\Paths;

/**
 * TempProvider - Geçici Dosya (Temporary) Klasörü Yönetimi 🕒
 */
class TempProvider extends BaseStorageProvider
{
    protected string $storageName = 'temp';
    protected bool $encrypted = false;
    protected string $format = 'raw';

    protected function getStorageDir(): string
    {
        $projectKey = $this->projectKey ?: project_key() ?: 'default';
        return Paths::project()->storage('temp/' . $projectKey);
    }
}
