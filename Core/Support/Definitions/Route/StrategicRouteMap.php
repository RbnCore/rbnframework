<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Definitions\Route;

/**
 * StrategicRouteMap - RBN Framework Otonom Rota Tanımları 🗺️🛰️⚓
 * 
 * Bu sınıf, framework genelinde modül bazlı CRUD işlemlerinin hangi metodlara
 * ve hangi URI desenlerine otomatik bağlanacağını belirleyen merkezi haritadır.
 * Registry-First yaklaşımı ile rotalar buradan yönetilir.
 */
class StrategicRouteMap
{
    /**
     * OTONOM ROTA HARİTASI 🗺️⚓
     * 
     * RBN Framework: "Zero-Code" routing için aksiyon-rota eşleşmeleri.
     * Key: Controller Aksiyonu (Metod)
     * Value: [HTTP Method, URI Path Pattern]
     */
    public const STRATEGIC_ACTIONS = [
        'index' => ['method' => 'GET', 'path' => '/'],
        'modal' => ['method' => 'GET', 'path' => 'modal/?([0-9]*)'],
        'update' => ['method' => 'POST', 'path' => 'save'],
        'delete' => ['method' => 'POST', 'path' => 'delete/([0-9]+)'],
        'destroy' => ['method' => 'POST', 'path' => 'destroy/([0-9]+)'],
        'status' => ['method' => 'POST', 'path' => 'toggle'],
        'bulkOrder' => ['method' => 'POST', 'path' => 'reorder'],
    ];
}
