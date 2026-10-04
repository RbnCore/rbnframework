<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Concerns\Contexts;

use Rbn\Framework\Core\Base\Concerns\BaseContextTrait;

/**
 * StorageContextTrait - Shared Core Vitals 🧩🎻⚓
 * 
 * RBN 3.5: Accesses core properties via BaseContextTrait hierarchy.
 * Masterpiece Refactoring: Optimized Lazy Loading and DNA guarantees.
 */
trait StorageContextTrait
{
    /** --- Shared DNA Hierarchy --- */
    use BaseContextTrait;


    /** @var \Rbn\Framework\Core\System\Storage\StorageManager|null Shared Global Storage Engine 📦 */
    private static ?object $sharedStorageInstance = null;

    /**
     * Initialize Storage Context (The Boot Sequence) 🚀
     */
    protected function initStorageContext(): array
    {
        $this->storage = $this->resolveStorage();

        return [
            'storage' => $this->storage
        ];
    }

    /**
     * Resolve Storage Engine from Hub 🧩
     * 
     * RBN 3.5: DNA garantili, akışkan ve tekil (Lazy Load) çözümleme.
     */
    protected function resolveStorage(): ?object
    {
        if (self::$sharedStorageInstance !== null) {
            return $this->storage = self::$sharedStorageInstance;
        }

        if ($this instanceof \Rbn\Framework\Core\System\Storage\StorageManager) {
            return self::$sharedStorageInstance = $this->storage = $this;
        }

        return self::$sharedStorageInstance = $this->storage = new \Rbn\Framework\Core\System\Storage\StorageManager();
    }

    /**
     * Paylasilan depolama motorunu SIFIRLA (reset API'si)
     *
     * B-13: `$sharedStorageInstance` hicbir kosulda sifirlanmadigi icin test
     * izolasyonu imkansizdi. Bu metot YALNIZCA test/teardown tarafindan
     * cagrilir; uretimde kimse cagirmaz.
     *
     * DAVRANIS ETKISI: cagrilmadigi surece mevcut paylasimli motor AYNEN
     * kullanilmaya devam eder. Proje baglami hash'i ile bolme gibi
     * DAVRANIS DEGISTIREN kismin bilincli olarak YAPILMADI (karar: Lena).
     */
    public static function forgetStorage(): void
    {
        self::$sharedStorageInstance = null;
    }

    /* --- Shorthand Storage Accessors --- */
    public function cache()
    {
        return $this->resolveStorage() ? $this->resolveStorage()->cache() : null;
    }
    public function logs()
    {
        return $this->resolveStorage() ? $this->resolveStorage()->logs() : null;
    }
    public function sessions()
    {
        return $this->resolveStorage() ? $this->resolveStorage()->sessions() : null;
    }

    /**
     * Direct Shorthand to Log into Global Workspace Logs (workspace/logs) 🌐
     */
    public function logWorkspace(?string $channel = null)
    {
        $logs = $this->logs();
        if ($logs) {
            $logs->workspace();
            if ($channel) {
                $logs->channel($channel);
            }
        }
        return $logs;
    }
}
