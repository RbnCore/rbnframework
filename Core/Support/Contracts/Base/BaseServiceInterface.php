<?php
 
namespace Rbn\Framework\Core\Support\Contracts\Base;

use Rbn\Framework\Core\Support\Contracts\Discovery\DiscoveryInterface;
 
/**
 * BaseServiceInterface - The "Chef" of the Architecture 👨‍🍳🎻
 * 
 * RULES OF THE CONSTITUTION:
 * 1. ORCHESTRATION ONLY: This service is a coordinator. Business logic belongs in specialized traits/handlers.
 * 2. NO MAGIC ALLOWED: Explicit methods only. Forbidden: __call, __get, __set.
 * 3. ENGINE DISCOVERY: Must provide fluent access to the core discovery sextet (service, model, helper, query, handler, constant).
 * 4. HUB STANDARDS: Must implement standard CRUD/Bulk operation wrappers.
 */
interface BaseServiceInterface extends DiscoveryInterface
{
    /**
     * Initial Boot Sequence for the service and its satellites 🎻🛰️
     * Must be implemented by all concrete services to declare their boot-time requirements.
     */
    public function boot(): void;

 
    // ====================================================================
    // HUB & DATA OPERATIONS (Constitutional Methods) ⚖️
    // ====================================================================
 
    /**
     * Find a record by primary key.
     */
    public function find(int $id): ?object;
 
    /**
     * Set the target ID for fluent operations.
     */
    public function withId(int $id): self;
 
    /**
     * Destroy a record.
     */
    public function destroy(?int $id = null): self;
 
    /**
     * Toggle the status of a record.
     */
    public function toggleStatus(?int $id = null, $status = null): self;
 
    /**
     * Synchronize and clear cache.
     */
    public function clearCache($customKeys = null): self;
 
    /**
     * Check if the last operation was successful.
     */
    public function success(): bool;
}
