<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnFactory\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\System\Paths\Paths;

/**
 * FactoryLockHandler - Süreçler arası kilit ve süreli tek-kullanım kaydı 🔒
 *
 * Paket tablolarına benzersiz indeks eklemeden eşzamanlı isteği seri hâle getirir: `Storage/factory-locks/`
 * altında `flock` dosyaları (sha256 adlı; proje anahtarı + anahtar). Çağıran anahtara kullanıcı girdisi
 * koyabilir; dosya adı her zaman hash'tir. Depolama yazılamıyorsa FAIL-CLOSED: kilit alınamaz / tek-kullanım reddedilir.
 */
class FactoryLockHandler extends BaseComponent
{
    private const STORAGE_SUBDIR = 'factory-locks';

    /**
     * $callback'i aynı anahtar için tek seferde bir süreç çalıştıracak şekilde yürütür.
     * @template T
     * @param callable():T $callback
     * @return T
     * @throws \RuntimeException kilit alınamadıysa (fail-closed)
     */
    public function synchronized(string $key, callable $callback): mixed
    {
        $file = $this->fileFor('lock', $key);
        $handle = $file !== null ? @fopen($file, 'c+b') : false;
        if ($handle === false || !flock($handle, LOCK_EX)) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            throw new \RuntimeException('factory: kilit alınamadı');
        }
        try {
            return $callback();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * Anahtarı $ttl saniye boyunca TEK KEZ sahiplenir.
     * @return bool true = bu çağrı ilk (kullanıldı olarak işaretlendi); false = süresi dolmamış kayıt var ya da depolama yok
     */
    public function claimOnce(string $key, int $ttl): bool
    {
        $file = $this->fileFor('once', $key);
        $handle = $file !== null ? @fopen($file, 'c+b') : false;
        if ($handle === false) {
            return false;
        }
        try {
            if (!flock($handle, LOCK_EX)) {
                return false;
            }
            $until = (int) trim((string) stream_get_contents($handle));
            if ($until > time()) {
                return false;
            }
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, (string) (time() + $ttl));
            fflush($handle);
            flock($handle, LOCK_UN);
            return true;
        } finally {
            fclose($handle);
            $this->sweep($ttl);
        }
    }

    private function fileFor(string $kind, string $key): ?string
    {
        $dir = $this->directory();
        if ($dir === null) {
            return null;
        }
        $scope = $this->handler('factoryConfig')->projectKey();
        return $dir . DIRECTORY_SEPARATOR . $kind . '-' . hash('sha256', $scope . "\n" . $key) . '.lock';
    }

    private function directory(): ?string
    {
        try {
            $dir = rtrim((string) Paths::project()->storage(self::STORAGE_SUBDIR), '/\\');
        } catch (\Throwable) {
            return null;
        }
        if ($dir === '' || (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir))) {
            return null;
        }
        return is_writable($dir) ? $dir : null;
    }

    /** Süresi çoktan dolmuş tek-kullanım (`once-*`) ve kilit (`lock-*`) dosyalarını seyrek olarak temizler (yaklaşık %2 çağrıda). */
    private function sweep(int $ttl): void
    {
        $dir = $this->directory();
        if ($dir === null || random_int(1, 50) !== 1) {
            return;
        }
        $expired = time() - max($ttl, 3600) * 2;
        foreach (['once-*.lock', 'lock-*.lock'] as $pattern) {
            foreach (glob($dir . DIRECTORY_SEPARATOR . $pattern) ?: [] as $file) {
                $mtime = @filemtime($file);
                if ($mtime !== false && $mtime < $expired) {
                    @unlink($file);
                }
            }
        }
    }
}
