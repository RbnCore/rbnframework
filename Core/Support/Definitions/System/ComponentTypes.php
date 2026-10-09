<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Definitions\System;

/**
 * ComponentTypes - Single Source of Truth for Framework Component Mapping 🧬🏛️⚓
 * 
 * RBN Framework: Centralized authority for component type suffixes,
 * pluralization mappings, and alias resolution.
 * Replaces redundant type maps across Discovery Engine and Registries.
 */
class ComponentTypes
{
    /**
     * Master Component Suffix to Singular Category Type Mapping 🎭
     */
    public const MAP = [
        'Handler'   => 'handler',
        'Provider'  => 'provider',
        'Query'     => 'query',
        'Model'     => 'model',
        'Service'   => 'service',
        'Helper'    => 'helper',
        'Driver'    => 'driver',
        'Validator' => 'validation',
        'Guard'     => 'guard',
        'Library'   => 'library',
        'Manager'   => 'manager',
        'Preset'    => 'preset',
        'Rule'      => 'rule',
        'Repository'=> 'repository',
        'Resolver'  => 'resolver',
        'Builder'   => 'builder',
        'Job'       => 'job',
        'Task'      => 'task',
        'Queue'     => 'queue',
        'Event'     => 'event',
        'Listener'  => 'listener',
        'Action'    => 'action',
    ];

    /**
     * Singular Category to Plural Registry Key Mapping 📦
     */
    public const PLURAL_MAP = [
        'alias'      => 'aliases',
        'service'    => 'services',
        'model'      => 'models',
        'helper'     => 'helpers',
        'metadata'   => 'metadata',
        'validation' => 'validations',
        'handler'    => 'handlers',
        'constant'   => 'constants',
        'provider'   => 'providers',
        'command'    => 'commands',
        'manager'    => 'managers',
        'rule'       => 'rules',
        'preset'     => 'presets',
        'repository' => 'repositories',
        'resolver'   => 'resolvers',
        'builder'    => 'builders',
        'job'        => 'jobs',
        'task'       => 'tasks',
        'queue'      => 'queues',
        'event'      => 'events',
        'listener'   => 'listeners',
        'action'     => 'actions',
    ];

    /**
     * Get all component suffix mappings 🎭
     */
    public static function typeMap(): array
    {
        return self::MAP;
    }

    /**
     * Convert singular component type to plural registry key 📦
     */
    public static function pluralize(string $type): string
    {
        $lowType = strtolower($type);
        return self::PLURAL_MAP[$lowType] ?? $lowType . 's';
    }
}
