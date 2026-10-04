<?php
/**
 * RBN Framework 3.0: High-Performance Database Engine 🎻⚡
 */
namespace Rbn\Framework\Core\Database\Engine\Traits;

use PDO;

/**
 * ExecutionTrait - The Query Execution Center ⚡
 * 
 * Handles raw queries, prepared statements, and error logging.
 */
trait ExecutionTrait
{
    /** @var int Global Query Counter for Boot Defense 🛡️ */
    private static int $bootQueryCount = 0;

    /**
     * [D-21] İFADE ÖNBELLEĞİ ÜST SINIRI.
     *
     * `self::$stmtCache` yalnız `reconnect()` ile temizleniyordu; her FARKLI SQL
     * kalıcı bir `PDOStatement` tutuyordu. Uzun ömürlü CLI (cron kervanı) ve
     * worker süreçlerinde bu sınırsız büyüme bellek sızıntısıdır (ölçüldü:
     * 50 benzersiz sorgu → 52 kayıt; süreç ömrü boyunca birikir).
     *
     * LRU: taşma olursa EN ESKİ kayıtlar atılır (en son kullanılanlar korunur),
     * `array_slice(..., true)` ile ANAHTARLAR korunur.
     */
    public const STMT_CACHE_LIMIT = 200;

    /** @var array<string, \PDOStatement> Cached Prepared Statements for high performance 🧠⚡ */
    private static array $stmtCache = [];

    /**
     * Resets the boot loop query counter to zero 🛡️⚓
     */
    public function resetBootQueryCount(): void
    {
        self::$bootQueryCount = 0;
    }

    /**
     * Core Query Wrapper 🧬
     */
    public function query(string $query, array $params = [])
    {
        // 🛠️ RBN 3.5 [PANIC BRAKE] 🏹 Atomic Constant check to prevent memory exhaustion ⚡
        if (defined('RBN_PANIC_ACTIVE')) {
            return false;
        }

        // 🛡️ RBN 3.5: [BOOT LOOP SENSOR] 🛰️⚓ (CLI modunda pasif)
        if (PHP_SAPI !== 'cli') {
            self::$bootQueryCount++;
            if (self::$bootQueryCount > 100) {
                rbn_panic(
                    "Too many database queries during bootstrap (>100). Last Query: $query",
                    "Boot Query Loop Detected"
                );
            }
        }

        try {
            $stmt = $this->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (\PDOException $e) {
            // 🛡️ Auto-Reconnect: MySQL server has gone away (2006) or Lost connection (2013) 🔄🔌
            $msg = $e->getMessage();
            $errCode = $e->errorInfo[1] ?? 0;

            if ($errCode === 2006 || $errCode === 2013 || str_contains($msg, 'server has gone away') || str_contains($msg, 'Lost connection')) {
                // [D-48] DÜZELTME: `reconnect()` parametresiz çağrılınca yalnız
                // O AN AKTİF OLAN bağlantıyı düşürüyordu. Otoyol yeniden
                // denemesi `$this->activeConnection` üzerinden PDO alıyor; aktif
                // bağlantı başka bir kategoriye (ör. common) geçmişse YANLIŞ
                // bağlantı düşürülür, ölü PDO yerinde kalır ve 2006/2013 kalıcı
                // hale gelir. Artık düşürülecek bağlantı ADIYLA verilir.
                $this->reconnect($this->activeConnection);
                $stmt = $this->prepare($query);
                $stmt->execute($params);
                return $stmt;
            }

            // 🛡️ RBN 3.5: [ENGINE ISOLATION] Absolute Terminal Failure 🏛️⚓
            throw $e;
        }
    }

    /**
     * Prepare a PDO statement (Memoized & Reusable) 🏗️
     */
    public function prepare(string $query): \PDOStatement
    {
        // [FW-DB-2A / D-01] Anahtar PDO NESNE KIMLIGINI de icermeli: ayni baglanti
        // adi altinda setPdo() ile DEGISEN PDO'da bayat ifade yeniden kullanilirsa
        // okuma eski baglantiyi, yazma yanlis baglantiyi okur (onbellek STATIK oldugu
        // icin bu yalniz bu nesneye degil, tum Database orneklerine de zarar verir).
        // spl_object_id() ayni PDO icin ayni, farkli PDO icin farkli kimlik verir:
        // ayni nesne + ayni SQL = onbellekten ayni ifade (kazanc korunur).
        $pdo = $this->getPdo();
        $hash = md5($query . ':' . $this->activeConnection . ':' . spl_object_id($pdo));
        if (!isset(self::$stmtCache[$hash])) {
            self::$stmtCache[$hash] = $pdo->prepare($query);

            // [D-21] LRU üst sınırı: taşma olursa en eski kayıtlar düşürülür.
            if (count(self::$stmtCache) > self::STMT_CACHE_LIMIT) {
                self::$stmtCache = array_slice(
                    self::$stmtCache,
                    -self::STMT_CACHE_LIMIT,
                    null,
                    true
                );
            }
        }
        return self::$stmtCache[$hash];
    }

    /**
     * Execute a query without returns ⚔️
     */
    public function execute(string $query, array $params = []): bool
    {
        return $this->query($query, $params) !== false;
    }

    /**
     * Raw query execution (Select multiple) 🔍
     */
    public function raw(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    /**
     * Raw query execution (Select first result) 🎯
     */
    public function rawFirst(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        if (!$stmt) return null;
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Raw query implementation of execute 🛠️
     */
    public function rawExecute(string $sql, array $params = []): bool
    {
        return $this->execute($sql, $params);
    }
}
