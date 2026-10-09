<?php
/**
 * High-Performance Database Engine 🎻🌉
 */
namespace Rbn\Framework\Core\Database\Engine\Traits;

use Rbn\Framework\Core\Database\Engine\Providers\QueryBuilder;
use Rbn\Framework\Core\Database\Engine\Providers\SchemaBuilder;

/**
 * QueryBridgeTrait - The Builder Factory 🌉
 * 
 * Provides access to specialized Query and Schema builders.
 */
trait QueryBridgeTrait
{
    /**
     * Start a fluent query builder ⛓️
     */
    public function table(string $table): QueryBuilder
    {
        return new QueryBuilder($this, $table);
    }

    /**
     * Start a fluent schema builder for structural changes 🏗️
     */
    public function schema(string $table): SchemaBuilder
    {
        return new SchemaBuilder($this, $table);
    }

    /**
     * Get the last inserted ID for the active connection 🆔
     */
    public function getLastInsertId(): int
    {
        return $this->getProvider($this->activeConnection)->getLastInsertId();
    }
}
