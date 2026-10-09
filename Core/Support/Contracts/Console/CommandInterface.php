<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Contracts\Console;

/**
 * CommandInterface - The RBN Framework Contract for CLI Actions 🏹⚖️⚓
 * 
 * RBN Framework: Ensures all CLI commands follow the same
 * anatomical structure for discovery and programmatic execution.
 */
interface CommandInterface
{
    /**
     * Execute the command logic 🚀
     */
    public function execute(array $params = []): void;

    /**
     * Return the command description for the console 📖
     */
    public function description(): string;

    /**
     * Map of commands handled by this class 🎡 map['cmd' => ['desc' => '...', 'method' => '...']]
     */
    public function getCommands(): array;
}
