<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Hosting\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * CPanelDomainsHandler - Specialized Bridge for Domain Management in Core 🏰🌐
 * 
 * RBN 3.5 Masterpiece Standard.
 * @property \Rbn\Framework\Core\Services\Hosting\Providers\CPanelProvider $CPanelProvider
 */
class CPanelDomainsHandler extends BaseComponent
{
    /**
     * Fetch active domains data from cPanel UAPI 📡
     */
    public function list(): array
    {
        return $this->CPanelProvider->call('DomainInfo', 'list_domains');
    }
}
