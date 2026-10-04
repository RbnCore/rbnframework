<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Kernel;

use Rbn\Framework\Core\Support\Contracts\Kernel\StageInterface;

/**
 * Kernel - Orchestrates the framework boot stages and lifecycle events. 🏰🚀⚓
 * 
 * RBN 3.5: Masterpiece Legacy Upgrade. 
 * Supports performance profiling, strategic hooks, and graceful termination.
 */
class Kernel
{
    private array $config;
    private array $stages = [];
    private array $context = [];
    private array $hooks = [];

    public function __construct(string $publicPath, array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Registers a strategic hook (event) for the kernel lifecycle. ⚓
     */
    public function addHook(string $name, callable $callback): self
    {
        $this->hooks[$name][] = $callback;
        return $this;
    }

    /**
     * Triggers all callbacks registered for a specific hook.
     */
    public function triggerHook(string $name, mixed ...$args): void
    {
        foreach ($this->hooks[$name] ?? [] as $callback) {
            $callback($this, ...$args);
        }
    }

    /**
     * Adds a stage to the boot pipeline.
     */
    public function addStage(StageInterface $stage): self
    {
        $this->stages[] = $stage;
        return $this;
    }

    /**
     * Runs all registered boot stages with performance tracking. ⏱️📈
     */
    public function boot(): void
    {
        $this->triggerHook('kernel.boot.start');

        $isDev = $this->config['dev_mode'] ?? (defined('RBN_DEV') && RBN_DEV);

        try {
            foreach ($this->stages as $stage) {
                $start = $isDev ? microtime(true) : 0;

                $stage->handle($this);

                if ($isDev) {
                    $duration = (microtime(true) - $start) * 1000; // ms
                    $this->context['profiling']['stages'][get_class($stage)] = round($duration, 4);
                }

                $this->triggerHook('kernel.boot.stage.after', $stage);
            }
        } catch (\Rbn\Framework\Core\Support\Exceptions\PreflightException $e) {
            // [RBN 3.5] DIAGNOSTIC DISPATCHER 🛡️⚓
            // Catch known boot failures and render through the specialized provider.
            \Rbn\Framework\Core\Services\Exception\Providers\PreflightProvider::renderFatal(
                $e->getType(),
                $e->getMessage(),
                $e->getHint(),
                $e->getCode()
            );
            exit;
        }

        $this->triggerHook('kernel.boot.complete');
    }

    /**
     * Handles the final cleanup and post-request logic. 🏁🧹
     */
    public function terminate(): void
    {
        $this->triggerHook('kernel.terminate');

        // Final context dump for profiling if dev mode
        if ($this->get('profiling')) {
            // Optional: Log or process profiling data
        }
    }

    /**
     * Context Accessors
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    public function set(string $key, mixed $value): void
    {
        $this->context[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->context[$key] ?? $default;
    }
}
