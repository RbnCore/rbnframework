<?php
declare(strict_types=1);

namespace Rbn\Framework\Bundles\RbnSuite\RbnShield\Support\Contracts\Providers;

/**
 * IpGuardProviderInterface - Strategy for IP Access Management 🛰️⚖️
 * 
 * RBN Framework: Rules for fetching, saving and validating IP block/whitelist status.
 */
interface IpGuardProviderInterface
{
    /**
     * Fetch blocked IPs based on criteria 🔍
     */
    public function fetch(array $options): array;

    /**
     * Save (Block or Update) an IP entry 💾
     */
    public function save(array $data): bool;

    /**
     * Remove an IP from the blacklist 🗑️
     */
    public function destroy(int $id): bool;

    /**
     * Specialized: Check if an IP is in the whitelist 🕊️
     */
    public function isWhitelisted(string $ip): bool;
}
