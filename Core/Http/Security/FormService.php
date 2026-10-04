<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Http\Security;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\Support\Contracts\Base\BaseServiceInterface;
use Rbn\Framework\Core\Http\Security\Handlers\CsrfHandler;
use Rbn\Framework\Core\Http\Security\Handlers\BotHandler;

/**
 * FormService - Gelişmiş Form Güvenlik ve Doğrulama Servisi 🛡️📝
 * 
 * RBN 3.5 "Masterpiece": Slim Gateway for form security orchestration.
 * Delegates the heavy lifting to FormGuardHandler (Atomic Actor).
 */
class FormService extends BaseService implements BaseServiceInterface
{
    /** @var CsrfHandler|null */
    protected ?CsrfHandler $csrfHandler = null;

    /** @var BotHandler|null */
    protected ?BotHandler $botHandler = null;

    /* --- Lazy Loading Logic --- */

    protected function csrf(): CsrfHandler
    {
        return $this->csrfHandler ??= $this->handler('csrf');
    }

    protected function bot(): BotHandler
    {
        return $this->botHandler ??= $this->handler('bot');
    }

    /* ==========================================================================
       [ PUBLIC API ]
       ========================================================================== */

    /**
     * CSRF Utility: Generate.
     */
    public function token(): array
    {
        return $this->csrf()->generate();
    }

    /**
     * Honeypot Utility: Get dynamic field name. 🛸
     */
    public function honeypotName(): string
    {
        return $this->bot()->getDynamicName();
    }

    /**
     * Executes all active security checks for form submissions.
     * Delegates to FormGuardHandler (Atomic Actor) for execution. 🛡️⚓
     */
    public function runSecurityChecks(array $data, array $options = []): array
    {
        return $this->handler('formGuard')->audit($data, $options);
    }
}
