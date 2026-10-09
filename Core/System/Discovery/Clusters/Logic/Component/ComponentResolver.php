<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Discovery\Clusters\Logic\Component;

/**
 * ComponentResolver - The Discovery Proxy Layer 🏹🛰️⚓
 * 
 * RBN Framework: Acts as a simplified static entry point.
 * Delegates all intelligence and resolution logic to the ComponentContext unit.
 */
class ComponentResolver
{
    /** @var ComponentContext|null Shared instance for the request */
    private static ?ComponentContext $context = null;

    /**
     * Get the active ComponentContext instance 🕵️‍♂️
     */
    private static function getContext(): ComponentContext
    {
        return self::$context ??= new ComponentContext();
    }

    /**
     * Resolve a component instance intelligently (Proxy to Context) 🎁
     */
    public static function resolve(object $context, string $type, ?string $targetProperty = null, bool $mandatory = true): ?object
    {
        return self::getContext()->resolve($context, $type, $targetProperty, $mandatory);
    }

    /**
     * Resolve via Magic Suffix (Proxy to Context) 🪄✨
     */
    public static function resolveSuffix(object $context, string $name): ?object
    {
        return self::getContext()->resolveSuffix($context, $name);
    }
}
