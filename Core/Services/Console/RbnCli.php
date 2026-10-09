<?php

namespace Rbn\Framework\Core\Services\Console;

use Rbn\Framework\Core\System\Registries\RbnSystemInfo;
use Rbn\Framework\Core\Services\Console\Base\BaseCommand;
use Rbn\Framework\Core\Services\Console\Base\ConsoleStyle;
use Rbn\Framework\Core\Base\Concerns\Data\InteractsWithProjectContextTrait;

/**
 * RbnCli - Framework Command Line Interface Service (Modernized Console Edition)
 */
class RbnCli
{
    use InteractsWithProjectContextTrait;

    protected array $commands = [];
    protected array $handlers = [];
    protected bool $isMaster = false;
    protected ConsoleStyle $io;

    public function __construct()
    {
        $this->io = new ConsoleStyle();
        $this->discoverCommands();
    }

    /**
     * Discovery Orchestrator for CLI Commands 🏹🎡⚓
     * RBN Framework: Now uses the Centralized Registry Gate instead of raw glob scanning.
     * rbnframework/rbn  dosyası bağlantı yeridir. onu inceleyin.
     */
    protected function discoverCommands(): void
    {
        // 🎼 RBN Framework: Accessing the RBN Framework Command Map 🏛️📜
        $registry = new \Rbn\Framework\Core\System\Registries\SystemRegistry();
        $map = $registry->registerMap()['commands'] ?? [];

        foreach ($map as $alias => $className) {
            // Standardizing the FQCN (Handling Clean Strings)
            if (!str_starts_with($className, 'Rbn\\')) {
                $className = "Rbn\Framework\\" . ltrim($className, '\\');
            }

            if (class_exists($className)) {
                $handler = new $className($this->io);

                // Constitutional Verification: Must adhere to BaseCommand / CommandInterface ⚖️
                if ($handler instanceof BaseCommand) {
                    $this->handlers[] = $handler;
                    $cmds = $handler->getCommands();

                    foreach ($cmds as $name => $meta) {
                        $this->commands[$name] = [
                            'desc' => $meta['desc'],
                            'method' => $meta['method'],
                            'handler' => $handler
                        ];
                    }
                }
            }
        }
    }

    /**
     * Run the console application
     */
    public function run(array $argv): void
    {
        // 1. Clean & Extract Global Flags 🚀
        $filteredArgv = [];
        $explicitProject = null;

        foreach ($argv as $arg) {
            if (str_starts_with($arg, '--project=')) {
                $explicitProject = explode('=', $arg)[1] ?? '';
                $filteredArgv[] = $arg;
            } elseif ($arg === '--master') {
                $this->isMaster = true;
                $filteredArgv[] = $arg;
            } else {
                $filteredArgv[] = $arg;
            }
        }

        if (!empty($explicitProject)) {
            $this->switchProjectContext($explicitProject);
        }

        // Master Mode Selection
        if ($this->isMaster) {
            \Rbn\Framework\Core\Database\Database::getInstance()->connection('database_master');
        }

        $commandName = $filteredArgv[1] ?? 'list';

        if ($commandName === '--help' || $commandName === '-h' || $commandName === 'list') {
            $this->listCommands();
            return;
        }

        if (!isset($this->commands[$commandName])) {
            ConsoleStyle::error("Komut bulunamadı: '{$commandName}'");
            $this->listCommands();
            return;
        }

        $command = $this->commands[$commandName];
        $params = array_slice($filteredArgv, 2);

        try {
            call_user_func([$command['handler'], $command['method']], $params);
        } catch (\Throwable $e) {
            ConsoleStyle::error($e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
        }
    }

    /**
     * List all discovered commands
     */
    public function listCommands(): void
    {
        ConsoleStyle::header(RbnSystemInfo::get('FRAMEWORK_NAME') . " CLI v" . RbnSystemInfo::get('FRAMEWORK_VERSION') . ($this->isMaster ? " (MASTER MODE)" : ""));

        echo " Kullanım:\n  php rbn [komut] [parametreler]\n\n";
        echo " \033[0;32mMüsait Komutlar:\033[0m\n";

        ksort($this->commands);
        foreach ($this->commands as $name => $cmd) {
            echo "  \033[0;32m" . str_pad($name, 20) . "\033[0m " . $cmd['desc'] . "\n";
        }
        echo "\n";
    }
}

