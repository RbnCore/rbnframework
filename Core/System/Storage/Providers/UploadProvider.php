<?php

namespace Rbn\Framework\Core\System\Storage\Providers;

use Rbn\Framework\Core\System\Storage\Base\BaseStorageProvider;
use Rbn\Framework\Core\System\Paths\Paths;

/**
 * UploadProvider - Kullanıcı Yüklemeleri Klasörü Yönetimi 📁
 */
class UploadProvider extends BaseStorageProvider
{
    protected string $storageName = 'uploads';
    protected bool $encrypted = false;
    protected string $format = 'raw';

    protected function getStorageDir(): string
    {
        $projectKey = $this->projectKey ?: project_key() ?: 'default';
        // 🎯 RBN 3.0 Standard: Project Root Uploads (Isolated by projectKey)
        return Paths::project()->uploads($projectKey);
    }
}
