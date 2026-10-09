<?php

namespace Rbn\Framework\Core\System\Storage;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\System\Storage\Drivers\UniversalFileDriver;

/**
 * StorageManager - RBN Merkezi Depolama Orkestra Şefi (Hub) 🎻
 * 
 * Tüm depolama alt sistemlerini (Cache, Log, Session, Traffic vb.) 
 * otomatik olarak keşfeder ve yönetir.
 * 
 * Kullanım:
 *   $storage->cache()->set('key', 'value');
 *   $storage->logs()->log('error', 'Message');
 *   $storage->viewCompile()->clearAll(); // Dinamik Keşif!
 */
class StorageManager extends BaseService
{
    private ?UniversalFileDriver $driver = null;
    private array $providers = [];

    /**
     * Evrensel Sürücü (Lazy Loading)
     */
    public function driver(): UniversalFileDriver
    {
        if ($this->driver === null) {
            $this->driver = new UniversalFileDriver();
        }
        return $this->driver;
    }

    /**
     * Dinamik Provider Keşfi (Magic Call) 🔮
     * CamelCase (cache) -> PascalCase (CacheProvider) dönüşümü yapar.
     */
    public function __call(string $name, array $arguments)
    {
        if (isset($this->providers[$name])) {
            return $this->providers[$name];
        }

        // PascalCaseProvider formatına çevir (Try plural first, then singular)
        $className = ucfirst($name) . 'Provider';
        $fullClass = "\\Rbn\Framework\Core\System\\Storage\\Providers\\{$className}";

        if (!class_exists($fullClass) && str_ends_with($name, 's')) {
            $singular = substr($name, 0, -1);
            $className = ucfirst($singular) . 'Provider';
            $fullClass = "\\Rbn\Framework\Core\System\\Storage\\Providers\\{$className}";
        }

        if (class_exists($fullClass)) {
            $this->providers[$name] = new $fullClass($this->driver());
            return $this->providers[$name];
        }

        // Fallback: Legacy service lookup if it's not a provider
        return $this->service($className) ?: $this->service(ucfirst($name) . 'Manager');
    }

    /**
     * Provider Varlığını Kontrol Et (Zero-Code Bridge)
     */
    public function hasProvider(string $name): bool
    {
        $className = ucfirst($name) . 'Provider';
        return class_exists("\\Rbn\Framework\Core\System\\Storage\\Providers\\{$className}");
    }

    /**
     * [IDE Completion] - Cache Erişimi
     * @return \Rbn\Framework\Core\System\Storage\Providers\CacheProvider
     */
    public function cache()
    {
        return $this->__call('cache', []);
    }

    /**
     * [IDE Completion] - Log Erişimi
     * @return \Rbn\Framework\Core\System\Storage\Providers\LogProvider
     */
    public function logs()
    {
        return $this->__call('logs', []);
    }

    /**
     * [IDE Completion] - Session Erişimi
     * @return \Rbn\Framework\Core\System\Storage\Providers\SessionProvider
     */
    public function sessions()
    {
        return $this->__call('sessions', []);
    }

    /**
     * [IDE Completion] - Traffic Erişimi
     * @return \Rbn\Framework\Core\System\Storage\Providers\TrafficProvider
     */
    public function traffic()
    {
        return $this->__call('traffic', []);
    }

    /**
     * [IDE Completion] - Yedekleme Erişimi
     * @return \Rbn\Framework\Core\System\Storage\Providers\BackupProvider
     */
    public function backups()
    {
        return $this->__call('backups', []);
    }

    /**
     * [IDE Completion] - Yükleme Erişimi
     * @return \Rbn\Framework\Core\System\Storage\Providers\UploadProvider
     */
    public function uploads()
    {
        return $this->__call('uploads', []);
    }

    /**
     * [IDE Completion] - Dışa Aktarma Erişimi
     * @return \Rbn\Framework\Core\System\Storage\Providers\ExportProvider
     */
    public function exports()
    {
        return $this->__call('exports', []);
    }

    /**
     * [IDE Completion] - Görünüm Derleme Erişimi
     * @return \Rbn\Framework\Core\System\Storage\Providers\ViewProvider
     */
    public function view()
    {
        return $this->__call('view', []);
    }

    /**
     * [IDE Completion] - Geçici Dosya Erişimi
     * @return \Rbn\Framework\Core\System\Storage\Providers\TempProvider
     */
    public function temp()
    {
        return $this->__call('temp', []);
    }

    /**
     * 🌪️ Sistem Temizliği (Cache, Logs, Sessions) 
     * Dinamik olarak kayıtlı tüm birimleri temizleyebilir.
     */
    public function purgeAll(): array
    {
        $results = [
            'cache' => $this->cache()->clearAll(),
            'logs' => $this->logs()->clearAll(),
            'sessions' => $this->sessions()->clearAll()
        ];

        return [
            'success' => !in_array(false, $results, true),
            'message' => 'Sistem geçici verileri (Cache, Logs, Sessions) başarıyla temizlendi.',
            'data' => $results
        ];
    }
}
