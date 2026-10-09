<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Concerns\Identity;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\System\Discovery\Engine\DiscoveryEngine;
use Rbn\Framework\Core\Services\Exception\Concerns\Shield;

/**
 * SovereignIdentity - Total Module Metadata Hub 🏛️⚓
 * 
 * RBN Framework: Data object trait that consolidates all module/sub-module identity strings.
 * Consolidates all DNA properties formerly residing in BaseContextTrait.
 */
trait SovereignIdentity
{
    /** --- Centralized DNA Properties (Physical Source) --- */
    protected ?BaseService $rbn = null;
    protected ?DiscoveryEngine $discover = null;
    protected ?Shield $shield = null;

    /** @var object|null Standard Satellite Contexts (RBN Framework DNA) 🛰️⚓ */
    protected ?object $service = null;
    protected ?object $model = null;
    protected ?object $activeService = null;
    protected ?object $handler = null;
    protected ?object $provider = null;
    protected ?object $query = null;
    protected ?object $helper = null;

    /** @var object|null Core Service Gateways 🏛️⚓ */
    protected ?object $storage = null;
    protected ?object $http = null;
    protected $proxy = null;

    /** @var object|null Global HTTP Vitals 📥📤 */
    protected ?object $request = null;
    protected ?object $response = null;
    protected $Route = null;
    protected ?object $remote = null;

    /** @var string|null Current rendering context */
    protected ?string $context = null;

    /** @var \Rbn\Framework\Core\Base\Data\BaseConfig|object|string|null RBN Framework Module Data Hub 🛰️ */
    protected object|string|null $moduleData = null;

    /** @var string|null Active Discovery Scopes */
    protected ?string $module = null;
    protected ?string $sub_module = null;
    protected ?string $hub = null;
    protected ?string $panel = null;
    protected ?string $modalView = null;

    /** @var object|null The Hub's Active Controller Instance 🎮⚓ */
    protected ?object $activeController = null;

    /** @var mixed Universal Model Binding (Standard: $targetModel) 🛰️ */
    protected $targetModel = null;

    /** @var string|null Project Identity (Dinamik) 🚀🛰️⚓ */
    protected ?string $appName = null;
    protected ?string $appSlogan = null;
    protected ?string $appTitle = null;
    protected ?string $projectKey = null;
    protected ?string $projectGroup = null;
    protected ?string $appVersion = null;
}
