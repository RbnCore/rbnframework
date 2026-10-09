<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Console\Jobs\Cleanup;

use Rbn\Framework\Core\System\Paths\Paths;
use Rbn\Framework\Core\Base\Attributes\Component;

/**
 * ServerCleanupJob - Sunucu / Workspace Seviyesi Otonom Temizlik Görevi 🌐🧼
 * 
 * RBN Framework Standard.
 * Workspace altındaki merkezi logları, tmp scratch, lscache ve .trash dosyalarını temizler.
 */
#[Component(alias: 'cleanup.server', type: 'job')]
class ServerCleanupJob extends BaseCleanupHandler
{
    /** @var array Tmp altında anında temizlenecek geçici sistem çöp ön ekleri 🗑️ */
    public const TMP_JUNK_PREFIXES = ['sess_', 'RCMTEMPthumb'];

    /** @var array Sunucu dizinleri varsayılan saklama süreleri (Gün) ⏳ */
    public const DEFAULT_RETENTION = [
        'tmp'     => 1, // 1 günden eski scratch ve çöp dosyaları
        'lscache' => 2, // 2 günden eski LiteSpeed önbellekleri
        'logs'    => 7, // 7 günden eski global loglar (Hata analizi için)
        'trash'   => 7, // 7 günden eski cPanel silinmiş çöpleri (.trash)
    ];

    /**
     * Sunucu genelindeki geçici verileri temizler
     */
    public function execute(array $retentionOverrides = []): array
    {
        $logDays = (int) ($retentionOverrides['logs'] ?? self::DEFAULT_RETENTION['logs']);
        $tmpDays = (int) ($retentionOverrides['tmp'] ?? self::DEFAULT_RETENTION['tmp']);
        $lscacheDays = (int) ($retentionOverrides['lscache'] ?? self::DEFAULT_RETENTION['lscache']);
        $trashDays = (int) ($retentionOverrides['trash'] ?? self::DEFAULT_RETENTION['trash']);

        $deletedLogs = $this->cleanServerLogs($logDays);
        $deletedTmp = $this->cleanServerTmp($tmpDays);
        $deletedLscache = $this->cleanServerLscache($lscacheDays);
        $deletedTrash = $this->cleanServerTrash($trashDays);

        $totalDeleted = $deletedLogs + $deletedTmp + $deletedLscache + $deletedTrash;

        return [
            'success' => true,
            'deleted_logs' => $deletedLogs,
            'deleted_tmp' => $deletedTmp,
            'deleted_lscache' => $deletedLscache,
            'deleted_trash' => $deletedTrash,
            'total' => $totalDeleted
        ];
    }

    /**
     * Merkezi framework loglarını temizler (workspace/logs) 📂
     */
    public function cleanServerLogs(int $days = 7): int
    {
        $globalLogsDir = Paths::workspace() . DIRECTORY_SEPARATOR . 'logs';
        return $this->cleanDirectoryFiles($globalLogsDir, $days);
    }

    /**
     * Workspace tmp/ dizini ve geçici scratch/çöp dosyalarını temizler 🧹
     */
    public function cleanServerTmp(int $days = 1): int
    {
        $tmpDir = Paths::workspace() . DIRECTORY_SEPARATOR . 'tmp';
        return $this->cleanDirectoryFiles($tmpDir, $days, self::TMP_JUNK_PREFIXES);
    }

    /**
     * Sunucu LiteSpeed Cache (lscache) dizinini temizler ⚡
     */
    public function cleanServerLscache(int $days = 2): int
    {
        $lscacheDir = Paths::workspace() . DIRECTORY_SEPARATOR . 'lscache';
        return $this->cleanDirectoryFiles($lscacheDir, $days);
    }

    /**
     * Sunucu cPanel Çöp Kutusu (.trash) dizinini temizler 🗑️
     */
    public function cleanServerTrash(int $days = 7): int
    {
        $trashDir = Paths::workspace() . DIRECTORY_SEPARATOR . '.trash';
        return $this->cleanDirectoryFiles($trashDir, $days);
    }
}
