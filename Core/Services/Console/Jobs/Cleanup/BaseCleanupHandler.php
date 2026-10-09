<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Console\Jobs\Cleanup;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * BaseCleanupHandler - Temizlik İşlemleri İçin Ortak Taban ve Güvenlik Zırhı 🛡️🧼
 * 
 * RBN Framework Standard.
 */
abstract class BaseCleanupHandler extends BaseComponent
{
    /** @var array Korumalı sabit dosya isimleri 🛡️ */
    public const PROTECTED_FILES = [
        '.gitignore',
        '.gitkeep',
        '.trash_restore',
        'index.html',
        'index.php',
        '.htaccess',
        'web.config',
        'robots.txt'
    ];

    /** @var array Silinmesi kesinlikle yasak olan korumalı kelime/desenler (Dosya adının başında veya içinde geçse bile silinmez) 🛡️ */
    public const PROTECTED_PATTERNS = [
        'api_',
        '_api_',
        'pulse_',
        '_pulse_',
        'google-',
        'credentials',
        'oauth',
        'token'
    ];

    /**
     * Dizin içerisindeki eski dosyaları ve anında silinecek çöp dosyaları korumalı olarak temizler 🧹
     *
     * @param string $dirPath Taranacak klasör yolu
     * @param int $days Saklanacak gün sayısı
     * @param array $immediatePrefixes Gün sınırına bakılmaksızın anında silinecek çöp ön ekleri (örn: ['sess_', 'RCMTEMPthumb'])
     */
    protected function cleanDirectoryFiles(string $dirPath, int $days, array $immediatePrefixes = []): int
    {
        if (!is_dir($dirPath)) {
            return 0;
        }

        $deletedCount = 0;
        $now = time();
        $limitTime = $now - ($days * 86400);

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dirPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            /** @var \SplFileInfo $item */
            foreach ($iterator as $item) {
                if ($item->isFile()) {
                    $filename = $item->getFilename();

                    // 🛡️ 1. Korumalı Sabit Dosyalar Kontrolü
                    if (in_array($filename, static::PROTECTED_FILES, true)) {
                        continue;
                    }

                    // 🛡️ 2. Korumalı Desen Kontrolü (api_, _api_, youtube_data, pulse_ vb. dosya adının neresinde olursa olsun korunur)
                    $isProtected = false;
                    foreach (static::PROTECTED_PATTERNS as $pattern) {
                        if (str_contains($filename, $pattern)) {
                            $isProtected = true;
                            break;
                        }
                    }
                    if ($isProtected) {
                        continue;
                    }

                    // 🗑️ 3. Anında Silinecek Geçici Çöp Dosyası Kontrolü
                    $isImmediateJunk = false;
                    foreach ($immediatePrefixes as $immPrefix) {
                        if (str_starts_with($filename, $immPrefix)) {
                            $isImmediateJunk = true;
                            break;
                        }
                    }

                    // 🛡️ 4. Süre Aşımı veya Çöp Durumuna Göre Silme
                    if ($isImmediateJunk || $item->getMTime() < $limitTime) {
                        @unlink($item->getRealPath());
                        $deletedCount++;
                    }
                } elseif ($item->isDir()) {
                    // Boş kalan klasörleri temizle (Sistem dizinleri hariç)
                    $this->removeEmptyDirectory($item->getRealPath());
                }
            }
        } catch (\Throwable $e) {
            // Hata durumunda sessizce devam et
        }

        return $deletedCount;
    }

    /**
     * Boş dizinleri güvenli bir şekilde siler
     */
    protected function removeEmptyDirectory(string $path): void
    {
        $dirName = basename($path);
        $systemProtectedDirs = ['logs', 'cache', 'sessions', 'framework', 'awstats', 'analog', 'webalizer', 'webalizerftp', 'pear'];

        if (in_array($dirName, $systemProtectedDirs, true)) {
            return;
        }

        $files = @scandir($path);
        if (is_array($files) && count($files) <= 2) { // Sadece . ve .. varsa
            @rmdir($path);
        }
    }
}
