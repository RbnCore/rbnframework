<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Exception;

use Throwable;
use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\Support\Contracts\Base\BaseServiceInterface;

/**
 * ExceptionService - The Ultimate Masterpiece Orchestrator 🎻🪐
 * 
 * RBN 3.5: [SYMMETRIC LAZY DISCOVERY] 🏛️🛰️✨
 * 
 * 
 * @property-read \Rbn\Framework\Core\Services\Exception\Handlers\ErrorAnalysisHandler $errorAnalysisHandler
 * @property-read \Rbn\Framework\Core\Services\Exception\Handlers\LogHandler $logHandler
 * @property-read \Rbn\Framework\Core\Services\Exception\Handlers\ExceptionHandler $exceptionHandler
 * @property-read \Rbn\Framework\Core\Services\Exception\Providers\DevelopmentProvider $developmentProvider
 * @property-read \Rbn\Framework\Core\Services\Exception\Providers\UserErrorProvider $userErrorProvider
 */
#[\AllowDynamicProperties]
class ExceptionService extends BaseService implements BaseServiceInterface
{
    /**
     * Boot the Exception Symphony ⚓
     * (Satellites are resolved dynamically!) 🧬✨
     */
    public function boot(): void
    {
        // 🛰️ Triggering Lazy Registration via Magic property access 🪄
        $this->errorAnalysisHandler;
        $this->logHandler;
        $this->exceptionHandler;
        $this->developmentProvider;
        $this->userErrorProvider;
    }

    /**
     * Resolve and Execute Exception Handling 🛰️💣
     */
    public function handle(Throwable $e): void
    {
        // 1. Analyze (Auto-resolves Handlers/ErrorAnalysisHandler) 🧪
        $data = $this->errorAnalysisHandler->analyze($e);
        $arrayData = (array) $data; // veri sınıfı yalnız özellik taşır (eski toArray)

        // 2. Log (Auto-resolves Handlers/LogHandler) 📓
        $this->logHandler->log($arrayData);

        // 3. Render (Auto-resolves Providers/DevelopmentProvider or UserErrorProvider) 🎭
        // RBN 3.5: If level is 'user', ALWAYS use UserErrorProvider (even in debug) 🛡️⚓
        if ($arrayData['level'] !== 'user' && $this->shouldShowRichError()) {
            $this->developmentProvider->render($arrayData, $e);
        } else {
            $this->userErrorProvider->render((int) ($arrayData['code'] ?? 500), $arrayData);
        }
    }

    /**
     * Centralized Authority: Should we show detailed diagnostics? 🩺🛰️
     */
    public function shouldShowRichError(): bool
    {
        // 🛡️ RBN 3.5: [AUTONOMOUS DEFENSE] 🏛️🛰️
        // Using the direct RBN_DEBUG constant to avoid circular loops with SettingsService.
        //
        // FW-KARAR-2 / Z-1: ayrica `RBN_DEV` kapisi (fail-closed). Aksi halde
        // uretimde `RBN_DEBUG=1` verildiginde ayrintili gelistirme ekrani
        // (mesaj + dosya + satir + kod parcalari) kullaniciya acilirdi.
        if (!(defined('RBN_DEV') && RBN_DEV === true)) {
            return false;
        }

        return defined('RBN_DEBUG') ? RBN_DEBUG : false;
    }

    /**
     * Access the internal Handler hub
     */
    // Satellites auto-resolved via BaseComponent Magic __get() 🪄✨
    // $this->exceptionHandler, $this->bootHandler, etc.
}
