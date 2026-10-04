<?php

namespace Rbn\Framework\Core\Database\Engine\Providers;

use PDO;
use PDOException;
use Rbn\Framework\Core\Support\Contracts\Database\DbProviderInterface;
use Rbn\Framework\Core\System\Config\Config;
use Rbn\Framework\Core\System\Config\Engine\Database\DatabaseConfig;

/**
 * MySqlProvider - High-Performance PDO Connection Motor 🏎️⚙️
 * 
 * RBN 3.5: Masterpiece Standard.
 * Strictly authoritative via DatabaseConfig DTOs.
 * Centralized under Config Hub (SSoT).
 */
class MySqlProvider implements DbProviderInterface
{
    private ?PDO $pdo = null;
    private string $name;
    private string $category;
    private ?DatabaseConfig $config = null;
    private ?float $lastActivityTime = null;

    /**
     * @param string $name Connection category (e.g. 'database_master', 'database_project')
     */
    public function __construct(string $name = 'database_project')
    {
        $this->name = $name;

        // 🧬 RBN 3.5: Strategic Mapping to Unified Standard Categories
        //
        // [FW-ALTYAPI-1 / B-05] FAIL-CLOSED: bilinmeyen ad ARTIK sessizce
        // `database_project`'e dusmuyor. Onceden `default` kolu sayesinde yazim
        // hatasi (orn. `database_mastrer`) proje veritabanina yonlendiriliyordu;
        // cok kiracili ortamda bu, proje A'nin verisinin proje B'ye yazilmasi
        // demekti. Artik hata MESAJI ada gore yazilir, sonuc belirsiz degildir.
        //
        // GERI UYUM: `default` ve `database_project` ayni kumeye gider (B-03).
        $this->category = match ($name) {
            'database_master' => 'database_master',
            'database_common' => 'database_common',
            'database_project', 'default' => 'database_project',
            default => throw new \InvalidArgumentException(sprintf(
                'Bilinmeyen veritabani baglanti adi: "%s" (gecerli adlar: database_project, '
                . 'database_master, database_common).',
                $name
            )),
        };

        // Standardized Config Retrieval (Automatic Guard Validation) 🛡️⚓
        $this->config = Config::get($this->category);
    }

    /**
     * Lazy Load PDO Connection
     */
    public function getPdo(): PDO
    {
        // 🛡️ OTONOM PROJE VE BAĞLANTI TAZELEME (Sıfır Hata Garantisi) 🚀
        $latestConfig = Config::get($this->category);
        if ($latestConfig !== null) {
            if (
                $this->config === null ||
                $this->config->database !== $latestConfig->database ||
                $this->config->host !== $latestConfig->host ||
                $this->config->user !== $latestConfig->user ||
                // [D-46] PAROLA DA KARŞILAŞTIRILIR: parola rotasyonunda/yedek
                // geri yüklemede kullanıcı-ayrı parola değişiyordu; bağlantı
                // ESKİ parolayla açılmış PDO'da kalıyor ve rotasyon etkisizdi.
                // `!==` tip duyarlıdır ama değerler zaten string'tir (null <> null).
                $this->config->password !== $latestConfig->password
            ) {
                $this->pdo = null;
                $this->config = $latestConfig;
            }
        }

        if ($this->pdo !== null && PHP_SAPI === 'cli') {
            $now = microtime(true);
            if ($this->lastActivityTime !== null && ($now - $this->lastActivityTime) > 10.0) {
                try {
                    $this->pdo->query('SELECT 1');
                } catch (\Throwable $e) {
                    // [D-47] `PDOException` YETMEZ: ağ zaman aşımı/soket hatası
                    // PDO sürücüsüne bağlı olarak `Error`/`RuntimeException`
                    // yolundan gelir; bu yol yakalanmadığında ÖLÜ bağlantı
                    // `$pdo`'da kalıyor ve CLI'da her sonraki sorgu patlıyordu.
                    // Yakalanan değer LOGLANMAZ (bağlantı hatası, veri değil).
                    $this->pdo = null;
                }
            }
        }

        if ($this->pdo === null) {
            $this->connect();
        }

        $this->lastActivityTime = microtime(true);
        return $this->pdo;
    }

    /**
     * Internal Connection Logic 🔌
     */
    private function connect(): void
    {
        // 🎼 RBN 3.5: [INTEGRITY SEAL] 🛡️⚓
        if ($this->config === null) {
            throw new \Rbn\Framework\Core\Support\Exceptions\PreflightException(
                "Bağlantı Ayarları Eksik! [{$this->category}]",
                "Veritabanı motoru yapılandırma bilgilerine erişemedi.",
                "Config::get('{$this->category}') çıktısını ve ilgili .env/settings dosyalarını kontrol edin."
            );
        }

        // 🎼 RBN 3.5: Pure Authoritative DSN Construction (Zero Legacy Overrides) 🏺⚖️🛡️⚓
        $dsn = "mysql:host={$this->config->host};dbname={$this->config->database};charset={$this->config->charset}";

        try {
            $this->pdo = new PDO($dsn, $this->config->user, $this->config->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
                1002 => "SET NAMES {$this->config->charset} COLLATE utf8mb4_unicode_ci"
            ]);
        } catch (PDOException $e) {
            // 🛡️ RBN 3.5: [ENGINE ISOLATION]
            // We do NOT call high-level services like 'logs' here to avoid recursion loops.
            // Authority is passed back to the caller (e.g. DatabaseGuard or Kernel).
            throw $e;
        }
    }

    /* --- Proxy Interface Methods --- */

    public function beginTransaction(): bool
    {
        return $this->getPdo()->beginTransaction();
    }
    public function commit(): bool
    {
        return $this->getPdo()->commit();
    }
    public function rollback(): bool
    {
        return $this->getPdo()->rollback();
    }
    public function inTransaction(): bool
    {
        return $this->getPdo()->inTransaction();
    }
    public function getLastInsertId(): int
    {
        return (int) $this->getPdo()->lastInsertId();
    }
}
