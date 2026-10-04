<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base;

use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * BaseAttribute - The Sovereign Attribute DNA 🧬🛰️⚓
 * 
 * RBN 3.5 Masterpiece Architecture.
 * Provides attributes with framework awareness via BaseService proxy, 
 * avoiding the heavy BaseComponent boot loop.
 */
abstract class BaseAttribute
{
    /** @var string|null The unique alias of the component */
    public ?string $alias = null;

    /** @var array Shared metadata across the discovery chain */
    public array $metadata = [];

    /** @var object|null Reference to the parent attribute (e.g. Bundle for a SubModule) */
    public ?object $parent = null;

    /**
     * Constructor for DNA injection 🧪
     * RBN 3.5: Autonomously hydrates the metadata vault and syncs identity.
     */
    public function __construct(array $metadata = [])
    {
        // 🎼 DNA Injection: Fill the vault
        $this->metadata = $metadata;

        // 🎼 Identity Sync: If an alias or name is provided, seal it.
        $this->alias = (string) ($metadata['alias'] ?? ($metadata['name'] ?? ($metadata['entity'] ?? $this->alias)));
    }

    /**
     * Set hierarchical parent context 🌳
     */
    public function setParent(object $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * Magic Accessor: Transparently retrieve metadata or framework services. 🪄✨
     * RBN 3.5: Direct proxy to BaseService to avoid inheritance loops.
     */
    public function __get(string $name)
    {
        // 1. Metadata Kasasına Bak (Vault Lookup)
        if (array_key_exists($name, $this->metadata)) {
            return $this->metadata[$name];
        }

        // 2. Framework Servislerine Sor (Service Proxy)
        // BaseComponent mirası yerine doğrudan BaseService üzerinden çözüyoruz.
        //
        // 🛡️ B-88: `isset($rbn->{$name})` TUZAKTI. `BaseService`/`BaseComponent`
        // `__isset` tanımlamıyor; `isset()` her zaman false döndüğü için bu
        // yol FİZİK OLARAK HİÇ ÇALIŞMIYOR ve framework erişimi sessizce null
        // kalıyordu. Artık doğrudan okunuyor.
        $rbn = BaseService::get();
        if ($rbn) {
            try {
                $cozulmus = $rbn->{$name};
            } catch (\Throwable $e) {
                // ÖLÇÜM: bilinmeyen adlarda `DiagnosticException` fırlatılıyor.
                // Attribute sınıfları (Module/SubModule/Component) keşif
                // zincirinin parçası; burada PATLAMAK çekirdeği kırardı.
                // Bu yüzden: logla + eski davranışı (null) koru.
                error_log('[RBN] BaseAttribute cozumlemesi basarisiz: ' . $name
                    . ' -> ' . get_class($e) . ': ' . $e->getMessage());
                return null;
            }

            if ($cozulmus !== null) {
                return $cozulmus;
            }
        }

        return null;
    }

    /**
     * Framework Helper: Access Configs 🛠️
     */
    protected function config(string $key, $default = null)
    {
        return BaseService::get()?->service('config')?->get($key) ?? $default;
    }
}
