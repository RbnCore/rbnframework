<?php

namespace Rbn\Framework\Core\Services\Console\Handlers;

use Rbn\Framework\Core\Services\Console\Base\BaseCommand;
use Rbn\Framework\Core\System\Paths\Paths;

class GeneratorHandlers extends BaseCommand
{
    public function getCommands(): array
    {
        return [
            'make:controller' => ['desc' => 'Yeni bir Controller oluşturur', 'method' => 'makeController'],
            'make:model' => ['desc' => 'Yeni bir Model oluşturur', 'method' => 'makeModel'],
            'make:request' => ['desc' => 'Yeni bir FormRequest sınıfı oluşturur', 'method' => 'makeRequest'],
            'make:migration' => ['desc' => 'Yeni bir Migration oluşturur', 'method' => 'makeMigration'],
        ];
    }

    public function makeController(array $params): void
    {
        $name = $params[0] ?? null;
        if (!$name) {
            $this->error("Controller adı gerekli.");
            return;
        }

        $content = "<?php\n\nnamespace Rbn\Project\Core\Http\Controllers;\n\nuse Rbn\Framework\Core\Base\Web\BaseController;\n\nclass {$name} extends BaseController\n{\n    public function index()\n    {\n        // Code here\n    }\n}\n";
        $path = Paths::project()->core("Http/Controllers/{$name}.php");

        $dir = dirname($path);
        if (!is_dir($dir))
            mkdir($dir, 0755, true);

        file_put_contents($path, $content);
        $this->success("Controller oluşturuldu: {$path}");
    }

    public function makeModel(array $params): void
    {
        $name = $params[0] ?? null;
        if (!$name) {
            $this->error("Model adı gerekli.");
            return;
        }

        $table = strtolower($name) . 's';
        $content = "<?php\n\nnamespace Rbn\Project\Core\Models;\n\nuse Rbn\Framework\Core\Base\Data\BaseModel;\n\nclass {$name} extends BaseModel\n{\n    protected \$table = '{$table}';\n    protected \$primaryKey = 'id';\n}\n";
        $path = Paths::project()->core("Models/{$name}.php");

        $dir = dirname($path);
        if (!is_dir($dir))
            mkdir($dir, 0755, true);

        file_put_contents($path, $content);
        $this->success("Model oluşturuldu: {$path}");
    }

    public function makeRequest(array $params): void
    {
        $name = $params[0] ?? null;
        if (!$name) {
            $this->error("Request adı gerekli.");
            return;
        }

        $content = "<?php\n\nnamespace Rbn\Project\Core\Http\Requests;\n\nuse Rbn\Framework\Core\Http\FormRequest;\n\nclass {$name} extends FormRequest\n{\n    public function rules(): array\n    {\n        return [\n            // 'field' => 'required|min:3'\n        ];\n    }\n}\n";
        $path = Paths::project()->core("Http/Requests/{$name}.php");

        $dir = dirname($path);
        if (!is_dir($dir))
            mkdir($dir, 0755, true);

        file_put_contents($path, $content);
        $this->success("FormRequest oluşturuldu: {$path}");
    }

    public function makeMigration(array $params): void
    {
        $name = $params[0] ?? null;
        if (!$name) {
            $this->error("Migration adı gerekli.");
            return;
        }

        $timestamp = date('Y_m_d_His');
        $fileName = "{$timestamp}_{$name}";
        $className = str_replace('_', '', ucwords($name, '_'));

        $content = "<?php\n\nuse Rbn\Framework\Core\Services\Console\Base\BaseMigration;\n\nclass {$className} extends BaseMigration\n{\n    public function up(): void\n    {\n        // \$this->db->rawExecute(\"CREATE TABLE ...\");\n    }\n\n    public function down(): void\n    {\n        // \$this->db->rawExecute(\"DROP TABLE ...\");\n    }\n}\n";
        $path = Paths::project()->root("database/migrations/{$fileName}.php");

        $dir = dirname($path);
        if (!is_dir($dir))
            mkdir($dir, 0755, true);

        file_put_contents($path, $content);
        $this->success("Migration oluşturuldu: {$path}");
    }
}

