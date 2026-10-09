<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Hosting;

use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * CPanelService - The RBN Framework Orchestrator for cPanel Integration 🏰🛰️⚓
 * 
 * RBN Framework Standard.
 * @property Handlers\CPanelMailHandler $CPanelMailHandler
 * @property Handlers\CPanelDomainsHandler $CPanelDomainsHandler
 * @property Providers\CPanelProvider $CPanelProvider
 */
class CPanelService extends BaseService
{
    /**
     * Bridge Gateway 🌉
     * RBN Framework: Access via the autonomous CPanel Provider.
     */
    public function bridge()
    {
        return $this->CPanelProvider;
    }

    /**
     * Proxy call to the Bridge 🛰️
     */
    public function call(string $module, string $function, array $params = []): array
    {
        return $this->bridge()->call($module, $function, $params);
    }

    /**
     * cPanel sunucu adı 🏷️
     * Kimlik bilgisi `secrets.php` `cpanel` bölümündedir; okuma Provider üzerinden (fail-closed).
     */
    public function host(): string
    {
        return $this->CPanelProvider->host();
    }

    /**
     * Mail Module Gateway 📨
     */
    public function mail()
    {
        return $this->CPanelMailHandler;
    }

    /**
     * Session/SSO Provider Gateway 🔗⚡
     */
    public function sso()
    {
        return $this->CPanelProvider;
    }

    /**
     * Domains Module Gateway 🌐
     */
    public function domains()
    {
        return $this->CPanelDomainsHandler;
    }
}
