<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Concerns\Contexts;

use Rbn\Framework\Core\Base\Concerns\BaseContextTrait;
use Rbn\Framework\Core\Http\Request;
use Rbn\Framework\Core\Http\Response;
use Rbn\Framework\Core\Http\RemoteRequest;
use Rbn\Framework\Core\Routes\Engine\Providers\RouteHandle;
use Rbn\Framework\Core\Base\Patterns\BaseProxy;

/**
 * HttpContextTrait - Shared HTTP Component Layer 🛰️⚓
 * 
 * RBN 3.5: Accesses core properties via BaseContextTrait hierarchy.
 * Prevents code redundancy while ensuring total linter compatibility.
 */
trait HttpContextTrait
{
    /** --- Shared DNA Hierarchy --- */
    use BaseContextTrait;


    /**
     * Initialize HTTP Context (The Boot Sequence) 🚀
     */
    protected function initHttpContext(): array
    {
        // 🎯 RBN 3.5: Masterpiece DNA Hub Access 🎻⚓
        // Try to fetch shared instances from the Grand Orchestrator (rbn) first.
        $hub = \Rbn\Framework\Core\Base\Services\BaseService::get();

        // 🛡️ B-14: bu beş alan TEK `if ($this->request === null)` bloğunda dolduruluyordu.
        // `request` önceden atanmışsa (ör. `beforeBoot()` içinden ya da üst sınıf
        // tarafından) blok ATLANIYOR ve `response`/`Route`/`remote`/`proxy`
        // DÖRDÜ DE null kalıyordu. Artık her alan KENDİ null kontrolüyle
        // doldurulur → yalnız boş olanlar üzerine yazılır (idempotent).
        if ($this->request === null) {
            $this->request = Request::capture();
        }

        if ($this->response === null) {
            $this->response = $hub->response ?? Response::getInstance();
        }

        // 🎼 RBN 3.5: Sovereign Recursion Guard 🛡️⚓
        // Prevent RouteHandle from instantiating itself recursively.
        if ($this->Route === null) {
            if ($this instanceof RouteHandle) {
                $this->Route = $this;
            } else {
                $this->Route = $hub->Route ?? new RouteHandle($this);
            }
        }

        // 🪐 RBN 3.5: Masterpiece Remote Connector 🛰️
        if ($this->remote === null) {
            if ($this instanceof RemoteRequest) {
                $this->remote = $this;
            } else {
                $this->remote = $hub->remote ?? new RemoteRequest();
            }
        }

        // 🎼 RBN 3.5: Masterpiece Decoupling
        if ($this->proxy === null) {
            $this->proxy = $hub->proxy ?? new BaseProxy('proxy');
        }

        return [
            'request' => $this->request,
            'response' => $this->response,
            'Route' => $this->Route,
            'remote' => $this->remote,
            'proxy' => $this->proxy
        ];
    }
}
