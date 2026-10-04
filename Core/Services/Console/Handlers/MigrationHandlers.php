<?php

namespace Rbn\Framework\Core\Services\Console\Handlers;

use Rbn\Framework\Core\Services\Console\Services\MigrationService;
use Rbn\Framework\Core\Services\Console\Services\MasterMigrationService;
use Rbn\Framework\Core\Services\Console\Base\ConsoleStyle;
use Rbn\Framework\Core\Services\Console\Base\BaseCommand;

class MigrationHandlers extends BaseCommand
{
    public function getCommands(): array
    {
        return [
            'migrate' => ['desc' => 'Bekleyen migrationları çalıştırır', 'method' => 'migrate'],
            'migrate:rollback' => ['desc' => 'Son migration batchini geri alır', 'method' => 'migrateRollback'],
            'migrate:status' => ['desc' => 'Migrationların durumunu gösterir', 'method' => 'migrateStatus'],
            // [FW-LICENCE] Master (sistem) DB migration'i: ASLA otomatik calismaz,
            // yalniz elle `rbn master:migrate [--rollback]`.
            'master:migrate' => ['desc' => 'Master veritabanı migrationlarını çalıştırır (yalnız elle)', 'method' => 'masterMigrate'],
            'master:migrate:status' => ['desc' => 'Master migration kayıtlarını gösterir', 'method' => 'masterMigrateStatus'],
        ];
    }

    /**
     * Master migration komutu: varsayilan calistirir, `--rollback` geri alir.
     */
    public function masterMigrate(array $params): void
    {
        $options = $this->parseOptions($params);
        $service = new MasterMigrationService();

        if (isset($options['rollback'])) {
            $res = $service->rollback();
            if ($res['status'] === 'nothing_to_rollback') {
                $this->info("Geri alınacak master migration batchi bulunamadı.");
            }
            foreach ($res['rolled_back'] ?? [] as $m) {
                $this->success("Geri alındı: {$m}");
            }
            return;
        }

        $res = $service->migrate();
        if ($res['status'] === 'nothing_to_migrate') {
            $this->info("Bekleyen master migration bulunamadı.");
            return;
        }

        foreach ($res['migrated'] as $m) {
            $this->success("Çalıştırıldı: {$m}");
        }

        // Anahtar DEGERLERI yazilmaz; sadece adet raporlanir.
        $rapor = $service->licenceCopyReport();
        $this->success(sprintf(
            'Lisans kopyası: %d projeden %d satır yazıldı, %d projede anahtar boş (satır yazılmadı).',
            $rapor['projects'],
            $rapor['copied'],
            $rapor['empty_key']
        ));
    }

    /**
     * Master migration kayıt durumu.
     */
    public function masterMigrateStatus(array $params): void
    {
        $status = (new MasterMigrationService())->getStatus();
        if (empty($status)) {
            $this->info("Master migration kaydı bulunamadı.");
            return;
        }

        ConsoleStyle::table(['migration', 'batch'], $status);
    }

    public function migrate(array $params): void
    {
        $res = $this->getMigrationService()->migrate();
        if ($res['status'] === 'nothing_to_migrate') {
            $this->info("Bekleyen migration bulunamadı.");
            return;
        }

        foreach ($res['migrated'] as $m) {
            $this->success("Çalıştırıldı: {$m}");
        }
    }

    public function migrateRollback(array $params): void
    {
        $res = $this->getMigrationService()->rollback();
        if ($res['status'] === 'nothing_to_rollback') {
            $this->info("Geri alınacak migration batchi bulunamadı.");
            return;
        }

        foreach ($res['rolled_back'] as $m) {
            $this->success("Geri alındı: {$m}");
        }
    }

    public function migrateStatus(array $params): void
    {
        $status = $this->getMigrationService()->getStatus();
        if (empty($status)) {
            $this->info("Migration kaydı bulunamadı.");
            return;
        }

        ConsoleStyle::table(['migration', 'batch'], $status);
    }

    protected function getMigrationService(): MigrationService
    {
        return new MigrationService();
    }
}
