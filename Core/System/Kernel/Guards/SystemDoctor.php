<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\System\Kernel\Guards;

use Rbn\Framework\Core\System\Paths\Paths;
use Rbn\Framework\Core\System\Kernel\Base\BaseGuard;

/**
 * System Doctor - Core Survival Guard 🩺🏗️
 */
class SystemDoctor extends BaseGuard
{
    /** @var string|null Expected reference key for the project */
    private static ?string $expectedKey = null;

    /**
     * Sets the reference project key before diagnostic starts.
     */
    public static function setExpectedKey(?string $key): void
    {
        self::$expectedKey = $key;
    }

    /**
     * Run absolute core diagnostics. 
     * Halts immediately if catastrophic failure detected.
     */
    public static function check(): void
    {
        // [RBN SYNC] In this stage, Paths class is already initialized by EnvDiscovery.
        // We can use it to avoid duplicate path calculations.

        // 1. Validate Composer Autoloader
        $vendorAutoload = Paths::framework()->vendor('autoload.php');
        if (!file_exists($vendorAutoload)) {
            self::fail(
                "Vendor bağımlılıkları veya autoload dosyası bulunamadı.\n(Yol: {$vendorAutoload})",
                "'composer install' komutunu çalıştırarak bağımlılıkları yüklediğinizden emin olun.",
                'Kayıp Çekirdek Dosyaları'
            );
        }

        // 2. Validate Project Directory
        $projectPath = Paths::project()->root();
        if (!is_dir($projectPath)) {
            self::fail(
                "Proje talep edildi ancak diskte klasörü bulunamadı.\n(Aranan Yol: {$projectPath})",
                "Projeyi oluşturduğunuz klasör isminin 'projects' dizini içinde mevcut olduğundan emin olun.",
                'Kayıp Proje Dizini'
            );
        }

        // 1. Validate Project Skeleton & Permissions (Permission Doctor 🩺)
        PermissionDoctor::check();

        // 2. Validate .htaccess (Apache Presence Check)
        $isApache = strpos($_SERVER['SERVER_SOFTWARE'] ?? '', 'Apache') !== false;
        if ($isApache) {
            $htaccessFile = Paths::project()->public('.htaccess');
            if (!file_exists($htaccessFile)) {
                self::fail(
                    "Apache sunucusu tespit edildi ancak web kök dizininde '.htaccess' dosyası bulunamadı.\n(Yol: {$htaccessFile})",
                    "RBN Framework linklerinin ve güvenliğinin çalışması için bu dosya gereklidir.",
                    'Kayıp Rota Yapılandırması (.htaccess)'
                );
            }
        }
    }
}
