<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Console\Base;

use Rbn\Framework\Core\Support\Contracts\Console\CommandInterface;
use Rbn\Framework\Core\Base\Concerns\Contexts\ServicesContextTrait;
use Rbn\Framework\Core\Base\Concerns\Contexts\StorageContextTrait;
use Rbn\Framework\Core\Base\Concerns\Data\InteractsWithProjectContextTrait;

/**
 * BaseCommand - The Sovereign Foundation for CLI Commands 🏹⚖️⚓
 * 
 * RBN 3.5 Masterpiece: Centralized authority for console I/O and universal discovery.
 * Constitutionally aligned with the Laws of Reusability and Structural DNA.
 */
abstract class BaseCommand implements CommandInterface
{
    /**
     * Unified Discovery Hub 🧬🎡⚓
     * RBN 3.5 Law: Mandatory use of base traits for non-redundant architecture.
     */
    use ServicesContextTrait;
    use StorageContextTrait;
    use InteractsWithProjectContextTrait;

    /** @var ConsoleStyle */
    protected $io;

    public function __construct(ConsoleStyle $io)
    {
        $this->io = $io;

        // 🎼 RBN 3.5: DNA Kökünü (rbn & discover) ayağa kaldır 🚀🔋
        // Law of Reusability: Using the standard boot sequence of the framework.
        $this->bootBaseContext();
    }

    /**
     * Return command description (To be implemented by child) 📖
     */
    public function description(): string
    {
        return "RBN CLI Komutu";
    }

    /**
     * Execute the command logic (To be implemented by child) 🚀
     */
    public function execute(array $params = []): void
    {
        $this->error("Bu komut henüz execute() metodunu uygulamamış!");
    }

    /* --- Console Output Tools --- */

    protected function info(string $msg): void
    {
        ConsoleStyle::info($msg);
    }

    protected function success(string $msg): void
    {
        ConsoleStyle::success($msg);
    }

    protected function error(string $msg): void
    {
        ConsoleStyle::error($msg);
    }

    protected function warning(string $msg): void
    {
        ConsoleStyle::warning($msg);
    }

    /**
     * CLI terminal parametrelerini (--key=val) çözümler ve diziye çevirir 📥
     */
    protected function parseOptions(array $params): array
    {
        $options = [];
        foreach ($params as $k => $v) {
            $raw = is_string($k) ? "--{$k}={$v}" : (string) $v;
            if (str_contains($raw, '=')) {
                [$flag, $val] = explode('=', $raw, 2);
                $cleanFlag = ltrim($flag, '-');
                $options[$cleanFlag] = trim($val);
            } elseif (str_starts_with($raw, '--')) {
                $cleanFlag = ltrim($raw, '-');
                $options[$cleanFlag] = true;
            }
        }
        return $options;
    }

    /**
     * Should return a map of ['command_name' => ['desc' => '...', 'method' => '...']] 🎡
     */
    abstract public function getCommands(): array;
}
