<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Syshub\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\System\Discovery\Clusters\Logic\Definition\Definition;

/**
 * SyshubDbConsoleHandler - Database Action Orchestrator ⚙️🛰️⚓
 * RBN 3.5 Masterpiece Standard.
 */
class SyshubDbConsoleHandler extends BaseComponent
{
    /**
     * Execute Raw SQL Query ⚡
     *
     * [D-27] Ham SQL çalıştırma yetkisi ve izi: yalnız geliştirici/süper yönetici,
     * tek ifade, her çalıştırma denetim kaydına (kim, hangi proje, değersiz SQL
     * ilk 200 karakter) yazılır.
     */
    public function executeQuery(string $sql): array
    {
        if (empty(trim($sql))) {
            return ['success' => false, 'message' => 'Sorgu boş olamaz.'];
        }

        $session = $this->session();
        $role = (string) $session->get('user_role', '');
        $allowed = self::isRawSqlRole(
            (bool) $session->get('is_logged_in', false),
            (bool) $session->get('is_master_developer', false),
            $role
        );

        if (!$allowed) {
            $this->auditSql('SQL_CONSOLE_DENIED_ROLE', $sql);
            return ['success' => false, 'message' => 'Bu işlem için yetkiniz yok.'];
        }

        if (self::hasMultipleStatements($sql)) {
            $this->auditSql('SQL_CONSOLE_DENIED_MULTI', $sql);
            return ['success' => false, 'message' => 'Aynı anda yalnızca tek bir SQL ifadesi çalıştırılabilir.'];
        }

        $this->auditSql('SQL_CONSOLE_EXECUTE', $sql);

        try {
            $res = $this->model('schemaDoctor')->executeRaw($sql);

            return [
                'success' => true,
                'type' => $res['type'],
                'data' => $res['data'],
                'affectedRows' => $res['affected'],
                'executionTime' => $res['time'] . ' ms',
                'message' => $res['type'] === 'EXECUTE' ? 'Başarılı.' : null
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Ham SQL'e izinli roller (giriş yapmış + master geliştirici / developer / superadmin).
     */
    public static function isRawSqlRole(bool $loggedIn, bool $masterDeveloper, string $role): bool
    {
        if (!$loggedIn) {
            return false;
        }

        return $masterDeveloper || in_array($role, ['developer', 'superadmin'], true);
    }

    /**
     * Sorguda birden fazla ifade var mı? Metin/tanımlayıcı/yorum içindeki `;`
     * sayılmaz; sondaki tek `;` (ve sonrasında yalnız boşluk/yorum) tek ifadedir.
     */
    public static function hasMultipleStatements(string $sql): bool
    {
        $seenSeparator = false;
        $len = strlen($sql);

        for ($i = 0; $i < $len; $i++) {
            $c = $sql[$i];
            $next = $sql[$i + 1] ?? '';

            // Yorumlar: ayraçtan sonra bile ifade sayılmaz.
            if (($c === '-' && $next === '-') || $c === '#') {
                $nl = strpos($sql, "\n", $i);
                $i = $nl === false ? $len : $nl;
                continue;
            }
            if ($c === '/' && $next === '*') {
                $end = strpos($sql, '*/', $i + 2);
                $i = $end === false ? $len : $end + 1;
                continue;
            }

            if (ctype_space($c)) {
                continue;
            }

            // Ayraçtan sonra yorum/boşluk dışında herhangi bir şey: ikinci ifade.
            if ($seenSeparator) {
                return true;
            }

            if ($c === ';') {
                $seenSeparator = true;
                continue;
            }

            if ($c === "'" || $c === '"' || $c === '`') {
                $i = self::skipQuoted($sql, $i, $c);
            }
        }

        return false;
    }

    /**
     * Denetim kaydı için SQL'i değersizleştirir: metinler/sayılar `?`, yorumlar
     * atılır, boşluklar tekleştirilir, ilk 200 karakter döner.
     */
    public static function maskForAudit(string $sql): string
    {
        $out = '';
        $len = strlen($sql);

        for ($i = 0; $i < $len; $i++) {
            $c = $sql[$i];
            $next = $sql[$i + 1] ?? '';

            if (($c === '-' && $next === '-') || $c === '#') {
                $nl = strpos($sql, "\n", $i);
                $i = $nl === false ? $len : $nl;
                $out .= ' ';
                continue;
            }
            if ($c === '/' && $next === '*') {
                $end = strpos($sql, '*/', $i + 2);
                $i = $end === false ? $len : $end + 1;
                $out .= ' ';
                continue;
            }
            if ($c === "'" || $c === '"') {
                $i = self::skipQuoted($sql, $i, $c);
                $out .= '?';
                continue;
            }
            if ($c === '`') {
                $start = $i;
                $i = self::skipQuoted($sql, $i, $c);
                $out .= substr($sql, $start, $i - $start + 1);
                continue;
            }

            $out .= $c;
        }

        $out = preg_replace('/\b\d+(?:\.\d+)?\b/', '?', $out) ?? '';
        $out = trim((string) preg_replace('/\s+/', ' ', $out));

        return mb_substr($out, 0, 200);
    }

    /**
     * Açılış tırnağının konumundan kapanış tırnağının konumunu döndürür
     * (ters eğik çizgi kaçışı ve çiftlenmiş tırnak desteklenir; kapanmamışsa son bayt).
     */
    private static function skipQuoted(string $sql, int $open, string $quote): int
    {
        $len = strlen($sql);

        for ($i = $open + 1; $i < $len; $i++) {
            $c = $sql[$i];

            if ($c === '\\' && $quote !== '`') {
                $i++;
                continue;
            }
            if ($c === $quote) {
                if (($sql[$i + 1] ?? '') === $quote) {
                    $i++;
                    continue;
                }
                return $i;
            }
        }

        return $len - 1;
    }

    /**
     * Denetim kaydı: kim, hangi proje, değersiz SQL'in ilk 200 karakteri.
     */
    private function auditSql(string $event, string $sql): void
    {
        try {
            $session = $this->session();
            $logs = $this->logs();
            if ($logs) {
                $logs->channel('security')->notice($event, [
                    'user_id' => (string) $session->get('user_id', ''),
                    'role'    => (string) $session->get('user_role', ''),
                    'project' => function_exists('active_project_key') ? active_project_key() : '',
                    'sql'     => self::maskForAudit($sql),
                ]);
            }
        } catch (\Throwable $e) {
            // Denetim yazılamazsa sorgu akışı değişmez; engelleme kararı yukarıda verildi.
        }
    }

    /**
     * Optimize Table(s) 🧹
     */
    public function optimize(?string $table = null): bool
    {
        $model = $this->model('schemaDoctor');
        $tables = $table ? [$table] : array_column($this->provider('syshubDbConsole')->getTables(), 'name');

        foreach ($tables as $t) {
            $model->optimize($t);
        }

        return true;
    }

    /**
     * Clean Install (Truncate system tables) 🛠️
     */
    public function cleanInstall(): bool
    {
        $model = $this->model('schemaDoctor');
        $tables = Definition::get('database_project', 'CLEANUP_ALLOWED_TABLES') ?? [];

        foreach ($tables as $t) {
            $model->truncateTable($t);
        }

        return true;
    }

    /**
     * Change Collation 🔠
     */
    public function changeCollation(string $table, string $collation): bool
    {
        return $this->model('schemaDoctor')->convertCollation($table, $collation);
    }

    /**
     * Drop Table 🔥
     */
    public function dropTable(string $table): bool
    {
        return $this->model('schemaDoctor')->dropTable($table);
    }

    /**
     * Delete Rows 🗑️
     */
    public function deleteRows(string $table, array $ids): bool
    {
        return $this->model('schemaDoctor')->deleteRows($table, $ids);
    }

    /**
     * Export SQL 💾
     */
    public function exportSql(string $table, ?array $ids = null): string
    {
        $model = $this->model('schemaDoctor');
        $pdo = $model->getPdo();

        $schema = $model->getTableSchema($table);
        $rows = $model->getTableData($table, $ids);

        $sql = "-- RBN Framework SQL Export\n-- Table: {$table}\n-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $sql .= $schema . ";\n\n";

        if (!empty($rows)) {
            $columns = array_keys($rows[0]);
            $colStr = "`" . implode("`, `", $columns) . "`";
            $sql .= "INSERT INTO `{$table}` ({$colStr}) VALUES \n";
            $values = [];
            foreach ($rows as $row) {
                $rowValues = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), array_values($row));
                $values[] = "(" . implode(", ", $rowValues) . ")";
            }
            $sql .= implode(",\n", $values) . ";\n";
        }

        return $sql;
    }
}
