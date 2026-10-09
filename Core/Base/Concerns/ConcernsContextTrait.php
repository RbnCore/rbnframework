<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Concerns;

use Rbn\Framework\Core\Base\Concerns\Contexts\ServicesContextTrait;
use Rbn\Framework\Core\Base\Concerns\Contexts\StorageContextTrait;
use Rbn\Framework\Core\Base\Concerns\Data\HybridAccessTrait;
use Rbn\Framework\Core\Base\Concerns\Contexts\HttpContextTrait;
use Rbn\Framework\Core\Base\Concerns\Contexts\ResourceResolverTrait;
use Rbn\Framework\Core\Support\Bridges\Traits\NormalizationTrait;
use Rbn\Framework\Core\Support\Bridges\Traits\ViewHelperTrait;
use Rbn\Framework\Core\Services\Exception\Concerns\ErrorHandlingTrait;
use Rbn\Framework\Core\Base\Services\Traits\Service\Engine\FileTransferServiceTrait;
use Rbn\Framework\Core\Base\Concerns\Data\InteractsWithProjectContextTrait;

/**
 * ConcernsContextTrait - RBN Framework Context Composer 🎻🪐⚓
 * 
 * RBN Framework: Composes all sub-context traits into a unified hub.
 * Centralizes the boot sequence and shared DNA hierarchy.
 */
trait ConcernsContextTrait
{
    /** 
     * Composing all sub-context traits 🎻
     * DNA properties ($rbn, $discover, etc.) are inherited via BaseContextTrait.
     */
    use BaseContextTrait;
    use ServicesContextTrait, StorageContextTrait, HttpContextTrait, ResourceResolverTrait;
    use ErrorHandlingTrait, HybridAccessTrait;
    use NormalizationTrait, ViewHelperTrait;
    use FileTransferServiceTrait;
    use InteractsWithProjectContextTrait;

    /**
     * Unified Context Orchestrator 🚀🧬⚓
     * Bütün hiyerarşiyi tek bir hamlede (Pre-flight) ayağa kaldırır.
     */
    public function bootConcernsContext(): array
    {
        // 🎼 RBN Framework: Önce DNA Kökü (rbn & discover) ayağa kalkar! 🧬
        // RBN Framework Boot Sequence: Artık her şey hiyerarşik sırayla çözülür.
        $this->bootBaseContext();

        return array_merge(
            $this->initServicesContext(), // 🛰️ Discovery & Active Scopes
            $this->initStorageContext(),  // 🧩 Storage, Cache & Sessions
            $this->initHttpContext()      // 🛫 Request & Response
        );
    }
}
