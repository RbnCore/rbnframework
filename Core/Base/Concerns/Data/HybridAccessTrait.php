<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Concerns\Data;

/**
 * HybridAccessTrait - The "Sovereign Duality" Engine 🏺🧬⚓
 * 
 * RBN 3.5 Masterpiece: Enables an object to behave both as an Object and an Array.
 * Seamlessly integrates with the BaseComponent's Magic Discovery System.
 */
trait HybridAccessTrait
{
    /**
     * Determine if an attribute exists at a given offset. (ArrayAccess Service) ⚙️
     */
    public function offsetExists(mixed $offset): bool
    {
        // B-16: `isset()` NULL değerli GERÇEK alanları da "yok" sayıyordu
        // (veritabanından gelen nullable kolonlar bu yüzden `isset($model['x'])`
        // sorgularında YANLIŞ negatif veriyordu). Doğru soru "alan var mı?" →
        // nesnenin gerçek alan listesinde anahtar aranır.
        if (!is_string($offset) && !is_int($offset)) {
            return false;
        }

        return array_key_exists((string) $offset, get_object_vars($this));
    }

    /**
     * Get the value at a given offset. (ArrayAccess Service) 🔱
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->{$offset} ?? null;
    }

    /**
     * Set the value at a given offset. (ArrayAccess Service) 🏗️
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->{$offset} = $value;
    }

    /**
     * Unset the value at a given offset. (ArrayAccess Service) 🗑️
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->{$offset});
    }

    public function toArray(): array
    {
        $data = get_object_vars($this);

        // Standard RBN Internal Filter (Protects the Engine from leakage) 🛡️⚓
        $internal = [
            'db',
            'table',
            'primaryKey',
            'connection',
            'queryProvider',
            'storage',
            'config',
            'discovery',
            'request',
            'route',
            'routeMap',
            'rbn',
            'discover',
            'shield',
            'service',
            'model',
            'activeService',
            'handler',
            'provider',
            'query',
            'helper',
            'http',
            'proxy',
            'response',
            'Route',
            'remote',
            'context',
            'moduleData',
            'module',
            'sub_module',
            'hub',
            'panel',
            'modalView',
            'activeController',
            'targetModel',
            'appName',
            'appSlogan',
            'appTitle',
            'projectKey',
            'projectGroup',
            'appVersion',
            'errors',
            'timestamps',
            'entityName',
            'bulkInputKey',
            // B-17: bu alanlar motorun KENDİ iç ayarlarıdır, veri değildir.
            // `get_object_vars()` iç alanları da döndürdüğü için `find()` ve
            // `toArray()` çıktısına karışıyordu.
            'scoped',
            'fillable',
            'guarded',
            'forcedProjectKey',
            'authorizedFields',
            'jsonFields'
        ];

        foreach ($internal as $key) {
            unset($data[$key]);
        }

        return $data;
    }
}
