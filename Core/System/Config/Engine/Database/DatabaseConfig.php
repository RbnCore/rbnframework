<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Config\Engine\Database;

use Rbn\Framework\Core\System\Discovery\Clusters\Logic\Definition\Definition;
use Rbn\Framework\Core\System\Config\Definitions\DbProfiles\MasterDbData;
use Rbn\Framework\Core\System\Config\Definitions\DbProfiles\CommonDbData;

/**
 * DatabaseConfig - Immutable Database Configuration DTO 🛡️⚙️⚓
 * 
 * RBN Framework: [RBN Framework DTO]
 * Encapsulates validated database credentials with self-transformation logic.
 */
readonly class DatabaseConfig
{
    public function __construct(
        public string $host = '127.0.0.1',
        public string $database = '',
        public string $user = 'root',
        public string $password = '',
        public string $charset = 'utf8mb4'
    ) {
    }

    /**
     * Strategic Factory: Loads and transforms data from a PHP configuration file 📂🧬
     */
    public static function fromFile(string $path, string $name): self
    {
        // 🛡️ RBN Framework: [IDENTITY SURVIVAL] 🏛️⚓
        // Direct bypass for Master & Common DBs to prevent discovery loops and ensure fixed identities.
        if (in_array($name, ['database_master', 'database_common'])) {
            return self::fromRaw([], $name);
        }

        if (!file_exists($path)) {
            return new self();
        }

        // 🎼 RBN Framework: [PURE PHP ONLY] - Environment files are no longer supported for DB.
        $rawData = (array) (include $path);

        return self::fromRaw($rawData, $name);
    }

    /**
     * Elite Factory: Transforms raw data into a DTO using Definition Mappings 🧬🦾✨
     */
    public static function fromRaw(array $data, string $name): self
    {
        // 1. Master DB: Always uses its own authoritative model credentials (MasterDbData)
        if ($name === 'database_master') {
            return new self(...DbProfileResolver::credentials(MasterDbData::class));
        }

        // 2. Common DB: Always uses its own authoritative model credentials (CommonDbData)
        if ($name === 'database_common') {
            return new self(...DbProfileResolver::credentials(CommonDbData::class));
        }

        $category = 'database_project';

        // [FW-DB-PROFIL] Proje DB profili (local/production) TEK çözücüden seçilir.
        // `DB_PROFILES` yoksa veri aynen döner (geriye uyum).
        $data = ProjectDbProfileResolver::resolve($data);

        // 2. Fetch the Source-of-Truth Mapping Dictionary 📖🛰️
        $map = Definition::get($category, 'KEYS_MAP');

        if (!$map) {
            return new self();
        }

        // 3. Return the Finalized Self
        return new self(
            host: $data[$map['host'] ?? ''] ?? '127.0.0.1',
            database: $data[$map['database'] ?? ''] ?? '',
            user: $data[$map['user'] ?? ''] ?? 'root',
            password: $data[$map['password'] ?? ''] ?? '',
            charset: $data[$map['charset'] ?? ''] ?? 'utf8mb4'
        );
    }

}
