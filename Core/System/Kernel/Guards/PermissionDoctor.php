<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\System\Kernel\Guards;

use Rbn\Framework\Core\System\Paths\Paths;
use Rbn\Framework\Core\System\Kernel\Base\BaseGuard;
use Rbn\Framework\Core\System\Discovery\Clusters\Structure\Folder\FolderContext;
use Rbn\Framework\Core\Support\Definitions\System\FolderMatrix;

/**
 * PermissionDoctor - Skeleton Integrity & Write Access Guard 🩺🦴
 */
class PermissionDoctor extends BaseGuard
{
    /**
     * Kullanılmayan kod katmanı klasörleri için üretilen atlama kayıtları. 📝
     * (Tanılama/raporlama ve birim testi için; istek başına yenilenir.)
     *
     * @var string[]
     */
    private static array $skipped = [];

    /**
     * Bu çalıştırmada ATLANAN (kullanılmayan) kod katmanı klasörlerinin kayıtları.
     *
     * @return string[]
     */
    public static function skippedFolders(): array
    {
        return self::$skipped;
    }

    /**
     * Executes the skeleton integrity and permission check.
     */
    public static function check(): void
    {
        self::$skipped = [];

        // 🛡️ [RBN BYPASS] CLI modunda çalışan (cron, terminal vb.) hiçbir süreçte klasör oluşturma/kontrolü yapma
        if (PHP_SAPI === 'cli') {
            return;
        }

        // 1. Get the Project DNA (Matrix Tree)
        $projectTree = FolderContext::name('PROJECT', true);
        if (!$projectTree)
            return;

        // 3. Resolve Project & Public Roots ⚓
        $projectPath = Paths::project()->root();
        $publicPath = Paths::publicRoot();

        if (empty($projectPath))
            return;

        // 4. Discover All Directories from the Matrix 🛰️🧬
        // We get relative paths first to decide which root to use for each
        $relativeDirectories = FolderContext::discoverDirectories($projectTree, '');

        foreach ($relativeDirectories as $relDir) {
            // 🎼 RBN Framework [RBN Framework RESOLUTION]: Decide the target root (Project vs Public) 🏺🪐⚓
            // If it starts with 'public', we redirect the check to the REAL web root.
            $isPublicAlias = str_starts_with($relDir, 'public');
            
            if ($isPublicAlias) {
                 $cleanRelPath = ltrim(substr($relDir, 6), DIRECTORY_SEPARATOR);
                 $dir = $publicPath . ($cleanRelPath ? DIRECTORY_SEPARATOR . $cleanRelPath : '');
                 $layerRoot = $publicPath;
            } else {
                 $dir = $projectPath . DIRECTORY_SEPARATOR . $relDir;
                 $layerRoot = $projectPath . DIRECTORY_SEPARATOR . self::layerRootName($relDir);
            }

            // 🚦 [DOCTOR-BOS-KLASOR] Kullanılmayan KOD katmanı klasörünü açma.
            // Çalışma zamanı klasörleri (Storage/cache/logs/sessions, public/*)
            // ASLA atlanmaz; yalnız koda ait ve projede hiç kullanılmayan
            // katman boş iskelet üretilmez. Var olan klasör (izin kontrolü için)
            // her zaman işlenir.
            if (!is_dir($dir) && !self::shouldCreate($relDir, $layerRoot)) {
                self::skip($relDir);
                continue;
            }

            // A. Self-Healing: Create if missing
            if (!is_dir($dir)) {
                if (!@mkdir($dir, 0755, true)) {
                    self::fail(
                        "Sistem gerekli bir dizini oluşturmaya çalıştı ancak başarısız oldu.",
                        "Lütfen ana dizin izinlerini (chmod) kontrol edin: {$dir}",
                        "Klasör Oluşturulamadı"
                    );
                }

                // Security: Add protection to newly created folders.
                // Yalnız ÇALIŞMA ZAMANI klasörlerinde güvenlik dosyası üretilir;
                // kod katmanına boş `index.html` bırakılmaz.
                if (FolderMatrix::isRuntimeLayer($relDir)) {
                    self::secureFolder($dir);
                }
            }

            // B. Selective Write Check: Only for storage and uploads
            if (self::requiresWriteAccess($dir)) {
                if (!is_writable($dir)) {
                    self::fail(
                        "Sistem bu dizine veri yazmak zorunda ancak izni yok.",
                        "Lütfen şu dizine yazma izni (chmod 755 veya 777) verin: <b>{$dir}</b>",
                        "Yazma İzni Hatası"
                    );
                }
            }
        }
    }

    /**
     * Bir MATRIX klasörünün KOD katmanı olup olmadığı ve o katmanın projede
     * gerçekten kullanılıp kullanılmadığı. 📦🚦
     *
     * Kural (basit, yeni sistem yok):
     *   - Çalışma zamanı katmanı  → daima oluşturulur.
     *   - Kod katmanı             → yalnız katmanın KÖKÜ projede gerçek bir
     *     dosya/dizin içeriyorsa oluşturulur; aksi halde ATLANIR.
     *
     * @param string $relDir     Matrix'ten gelen göreli yol (ör. `App\Services`)
     * @param string $layerRoot  Katman kökünün mutlak yolu (ör. `<proje>\App`)
     */
    private static function shouldCreate(string $relDir, string $layerRoot): bool
    {
        if (FolderMatrix::isRuntimeLayer($relDir)) {
            return true;
        }

        return self::layerHasContent($layerRoot);
    }

    /**
     * Katman kökünde `index.html`/`.htaccess` DIŞINDA gerçek bir içerik var mı? 🔍
     */
    private static function layerHasContent(string $layerRoot): bool
    {
        if (!is_dir($layerRoot)) {
            return false;
        }

        $entries = @scandir($layerRoot);
        if ($entries === false) {
            return false;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..' || $entry === 'index.html' || $entry === '.htaccess') {
                continue;
            }
            return true;
        }

        return false;
    }

    /**
     * Katman kökünün adı (göreli yolun ilk segmenti). 🌱
     */
    private static function layerRootName(string $relDir): string
    {
        $normalized = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $relDir);
        $normalized = trim($normalized, DIRECTORY_SEPARATOR);

        $first = strstr($normalized, DIRECTORY_SEPARATOR, true);
        return $first === false ? $normalized : $first;
    }

    /**
     * Kullanılmayan klasörü atla ve kaydını tut. 📝
     */
    private static function skip(string $relDir): void
    {
        $normalized = str_replace('\\', '/', $relDir);
        self::$skipped[] = 'atlandı: kullanılmayan klasör — ' . $normalized;
    }

    /**
     * Determines if a folder path requires explicit write access.
     */
    private static function requiresWriteAccess(string $path): bool
    {
        $normalized = strtolower(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path));

        // Check for 'storage' or 'uploads' in the path
        return (strpos($normalized, DIRECTORY_SEPARATOR . 'storage') !== false)
            || (strpos($normalized, DIRECTORY_SEPARATOR . 'uploads') !== false)
            || (strpos($normalized, DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'images') !== false);
    }

    /**
     * Adds basic security files to a folder.
     */
    private static function secureFolder(string $path): void
    {
        $htaccess = $path . DIRECTORY_SEPARATOR . '.htaccess';
        $index = $path . DIRECTORY_SEPARATOR . 'index.html';

        // Only add .htaccess to storage-sensitive areas
        if (strpos($path, 'Storage') !== false && !file_exists($htaccess)) {
            @file_put_contents($htaccess, "Order Deny,Allow\nDeny from all");
        }

        if (!file_exists($index)) {
            @file_put_contents($index, "");
        }
    }

}
