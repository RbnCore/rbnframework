<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Services\Traits\Service;

/* --- Service Engine Components --- */
use Rbn\Framework\Core\Base\Services\Traits\Service\Engine\BulkServiceTrait;
use Rbn\Framework\Core\Base\Services\Traits\Service\Engine\ReadServiceTrait;
use Rbn\Framework\Core\Base\Services\Traits\Service\Engine\CrudServiceTrait;
use Rbn\Framework\Core\Base\Services\Traits\Service\Engine\FileServiceTrait;
use Rbn\Framework\Core\Base\Services\Traits\Service\Blogcontent\ContentServiceTrait;
use Rbn\Framework\Core\Base\Services\Traits\Service\Blogcontent\ContentDataServiceTrait;
use Rbn\Framework\Core\Base\Services\Traits\Service\Blogcontent\ContentLegacyRedirectServiceTrait;

/**
 * ActionServiceTrait - RBN 3.5 Service Orchestrator Hub 🪐🎻
 * 
 * RBN 3.5: Strategic Hub that composes all service-related engine traits.
 * Manages cache, operation results, and model-provider delegation.
 */
trait ActionServiceTrait
{
    use ReadServiceTrait,
        CrudServiceTrait,
        BulkServiceTrait,
        FileServiceTrait,
        ContentServiceTrait,
        ContentDataServiceTrait,
        ContentLegacyRedirectServiceTrait;

    /**
     * RBN 3.0: 🎯 Fluent API Target ID Tracking
     */
    protected ?int $targetId = null;

    /**
     * RBN 3.0: ✨ Operation Result tracking (Fluent)
     */
    protected bool $lastResult = true;

    /**
     * RBN 3.5: 🛰️ Strategic Criteria (The Zero-Code Bridge)
     */
    protected array $criteria = [];

    /**
     * Fluent Entry Point: Set the target ID for operations. 🎯
     */
    public function withId(int $id): self
    {
        $this->targetId = $id;
        return $this;
    }

    /**
     * Check if the last operation in the chain was successful. ✅
     */
    public function success(): bool
    {
        return $this->lastResult;
    }



    /* ==========================================================================
       [ ORCHESTRATION ] - Cache Management 💾
       ========================================================================== */

    /**
     * Clear Cache for the current model 🧹🌍
     * 
     * RBN 3.5: Masterpiece Cache Sync logic.
     */
    public function clearCache($customKeys = null, ?string $projectKey = null): self
    {
        if (isset($this->storage)) {
            $cache = $this->storage->cache();

            // 🎯 RBN 3.5 Smart Reset: Clear specific keys or the whole hub 🧠🧹
            if ($customKeys === null) {
                if (method_exists($cache, 'clearAll')) {
                    $cache->clearAll($projectKey);
                } else {
                    $cache->clearAll();
                }
            } else {
                $keys = is_array($customKeys) ? $customKeys : [$customKeys];
                foreach ($keys as $key) {
                    $cache->delete($key);
                }
            }
        }

        return $this;
    }

}
