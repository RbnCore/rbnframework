<?php

namespace Rbn\Framework\Core\System\Storage\Providers;

use Rbn\Framework\Core\System\Storage\Base\BaseStorageProvider;
use Rbn\Framework\Core\System\Paths\Paths;

/**
 * ExportProvider - Dışa Aktarma (Excel, PDF vb.) Klasörü Yönetimi 📊
 */
class ExportProvider extends BaseStorageProvider
{
    protected string $storageName = 'exports';
    protected bool $encrypted = false;
    protected string $format = 'raw';

    protected function getStorageDir(): string
    {
        $projectKey = $this->projectKey ?: project_key() ?: 'default';
        return Paths::project()->storage('exports/' . $projectKey);
    }
}
