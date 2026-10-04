<?php

namespace Rbn\Framework\Core\Services\Console\Handlers;

use Rbn\Framework\Core\Database\Database;
use Rbn\Framework\Core\Services\Console\Base\ConsoleStyle;
use Rbn\Framework\Core\Services\Console\Base\BaseCommand;
use Rbn\Framework\Core\System\Config\Config;

class DatabaseHandlers extends BaseCommand
{
    public function getCommands(): array
    {
        return [
            'db:query' => ['desc' => 'Veritabanında SQL sorgusu çalıştırır', 'method' => 'dbQuery'],
            'db:schema' => ['desc' => 'Bir tablonun şemasını gösterir', 'method' => 'dbSchema'],
            'db:tables' => ['desc' => 'Veritabanındaki tabloları listeler', 'method' => 'dbTables'],
            'db:info' => ['desc' => 'Aktif veritabanı hakkında bilgi verir', 'method' => 'dbInfo'],
        ];
    }

    public function dbQuery(array $params): void
    {
        $query = $params[0] ?? null;
        if (!$query) {
            $this->error("SQL sorgusu gerekli.");
            return;
        }

        try {
            $db = Database::getInstance();
            $data = $db->raw($query);

            if (empty($data)) {
                $this->info("Sonuç bulunamadı.");
                return;
            }

            $headers = array_keys(reset($data));
            ConsoleStyle::table($headers, $data);
        } catch (\Exception $e) {
            $this->error("Sorgu hatası: " . $e->getMessage());
            $this->logs()?->channel('cli')->error("CLI DB Query Failed: " . $e->getMessage());
        }
    }

    public function dbSchema(array $params): void
    {
        $table = $params[0] ?? null;
        if (!$table) {
            $this->error("Tablo adı gerekli.");
            return;
        }

        try {
            $db = Database::getInstance();
            $schema = $db->raw("DESCRIBE `{$table}`");
            ConsoleStyle::table(['Field', 'Type', 'Null', 'Key', 'Default', 'Extra'], $schema);
        } catch (\Exception $e) {
            $this->error("Tablo bulunamadı: {$table}");
        }
    }

    public function dbTables(array $params): void
    {
        try {
            $db = Database::getInstance();
            $tables = $db->raw("SHOW TABLES");
            
            if (empty($tables)) {
                $this->info("Veritabanında tablo bulunamadı.");
                return;
            }

            $headers = explode('_', reset($tables)[array_key_first(reset($tables))]); // Fallback check
            $headers = ['Tables']; 
            
            $formatted = [];
            foreach ($tables as $t) {
                $formatted[] = ['Tables' => reset($t)];
            }

            ConsoleStyle::table($headers, $formatted);
        } catch (\Exception $e) {
            $this->error("Hata: " . $e->getMessage());
        }
    }

    public function dbInfo(array $params): void
    {
        try {
            $db = Database::getInstance();
            
            // 🧬 RBN 3.5: Detect Active Strategy (Master or Project)
            $activeConn = $db->connection();
            $category = ($activeConn === 'database_master') ? 'database_master' : 'database_project';
            
            // 🛡️ Get Authority-Validated DTO
            $config = Config::get($category);
            $dbName = $config->database;

            $tables = $db->raw("SHOW TABLES");
            $tableCount = count($tables);

            ConsoleStyle::header("Veritabanı Bilgileri");
            $this->info("Kategori      : " . $category);
            $this->info("Veritabanı    : " . $dbName);
            $this->info("Host          : " . $config->host);
            $this->info("Tablo Sayısı  : " . $tableCount);
            
            // Get size info
            $sizeInfo = $db->rawFirst("
                SELECT sum(data_length + index_length) / 1024 / 1024 AS size 
                FROM information_schema.TABLES 
                WHERE table_schema = ?", [$dbName]);
            
            $this->info("Toplam Boyut  : " . number_format($sizeInfo['size'] ?? 0, 2) . " MB");
            echo "\n";
        } catch (\Exception $e) {
            $this->error("Hata: " . $e->getMessage());
        }
    }
}
