<?php

namespace Rbn\Framework\Core\System\Storage\Providers;

use Rbn\Framework\Core\System\Storage\Base\BaseStorageProvider;
use Rbn\Framework\Core\System\Paths\Paths;

/**
 * ViewProvider - Derlenmiş Görünüm (Views) Klasörü Yönetimi 🖼️
 */
class ViewProvider extends BaseStorageProvider
{
    protected string $storageName = 'views';
    protected bool $encrypted = false;
    protected string $format = 'raw';

    protected function getStorageDir(): string
    {
        return Paths::project()->storage('framework/views');
    }

    /**
     * Tümünü Temizle (Yalnızca bu projeye ait derlenmiş görünümleri siler)
     */
    public function clearAll(): bool
    {
        $projectKey = $this->projectKey ?: (project_key() ?: 'default');
        $dir = $this->getStorageDir();
        $files = glob($dir . '/' . $projectKey . '_*.php');
        if ($files !== false) {
            foreach ($files as $file) {
                if (is_file($file))
                    @unlink($file);
            }
        }
        return true;
    }
}
