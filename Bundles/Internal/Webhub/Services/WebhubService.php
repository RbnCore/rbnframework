<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Webhub\Services;

use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * WebhubService - The RBN Framework Orchestrator for Webhub Bundle 🏛️🛰️⚓
 * 
 * RBN Framework: Centralized service for all Webhub related logic.
 * Discovers and manages WebhubProvider and WebhubHandler autonomously.
 * 
 * @property \Rbn\Framework\Bundles\Internal\Webhub\Providers\WebhubProvider $WebhubProvider
 */
class WebhubService extends BaseService
{
    /** --- Infrastructure DNA --- */
    protected $targetModel = 'settings';
    protected array $cacheKeys = ['webhub_settings'];

    /**
     * Boot: Initialize Service Components 🛰️🎯
     */
    public function boot(): void
    {
        // 🎼 RBN Framework [RBN Framework BINDING]
        // We link our registered bundle provider to the service engine.
        $this->provider = $this->provider('webhub');
    }

    /**
     * Point-Target Read helper 🕊️🏛️⚓
     * 
     * RBN Framework: Fluent criteria setter for group-based settings.
     */
    public function read(?string $groupKey = null): self
    {
        if ($groupKey) {
            $this->criteria['group_key'] = $groupKey;
        }

        return $this;
    }

    /**
     * Point-Target Criteria Setter 🏹🏹
     */
    public function withKey(string $key): self
    {
        $this->criteria['setting_key'] = $key;
        return $this;
    }

    /**
     * Fluent Persistence Hub (Delegation) 💾🛰️⚓
     * Pass the criteria context into the provider for strategic persistence.
     */
    public function save(mixed $id = null, array $data = []): self
    {
        // 🎼 RBN Framework: Strategic Payload Alignment
        if (is_array($id)) {
            $data = $id;
            $id = null;
        }

        $this->lastResult = (bool) $this->provider->save(array_merge($data, $this->criteria));
        $this->criteria = []; // Reset for fluent bulk cycles
        return $this;
    }
    /**
     * Strategic Action Bridge 🕊️🛰️⚓
     * 
     * RBN Framework: Orchestrates operations between controllers and providers autonomously.
     */
    public function action(?string $name = null, array $payload = []): self
    {
        if ($name) {
            $this->lastResult = $this->provider->action($name, array_merge($payload, $this->criteria));
            $this->criteria = [];
        }

        return $this;
    }
}
