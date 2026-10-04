<?php

namespace Rbn\Framework\Core\Services\Console\Base;

use Rbn\Framework\Core\Database\Database;

abstract class BaseMigration
{
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    abstract public function up(): void;
    abstract public function down(): void;
}
