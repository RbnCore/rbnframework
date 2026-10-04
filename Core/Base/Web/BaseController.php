<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Web;

use Rbn\Framework\Core\Support\Contracts\Base\BaseControllerInterface;
use Rbn\Framework\Core\Base\Web\Traits\Controller\ActionControllerTrait;
use Rbn\Framework\Core\Base\Web\Traits\Controller\ViewTrait;
use Rbn\Framework\Core\Base\BaseComponent;

/**
 * BaseController - The Central Command for RBN Applications 🎮🛰️⚓
 * 
 * RBN 3.5: Ultimate Masterpiece.
 * Artık mülkler (service, model vb.) hiyerarşik olarak ServicesContextTrait'ten gelir.
 */
abstract class BaseController extends BaseComponent implements BaseControllerInterface
{
    /**
     * Compose the controller with Web-specific abilities 🎮🎨
     */
    use ActionControllerTrait,
        ViewTrait;

    /**
     * Initialize the controller symphony 🎻⚓
     */
    public function __construct()
    {
        // 1. Kök DNA'yı (BaseComponent) ayağa kaldır 🧬 (Identity ve Config burada yüklenir)
        parent::__construct();

        // 2. Smart Hub Auto-Injection (Modül ve Servis Keşfi)
        $this->bootControllerSymphony();
    }

    /**
     * Orchestrate the smart injection and sovereign identity 🎻🛰️⚓
     * RBN 3.5: Simplified. Base DNA now handles attribute discovery.
     */
    protected function bootControllerSymphony(): void
    {
        // 1. Legacy Service/Model Sync Support 🎻⚓
        $serviceName = defined(static::class . '::SERVICE') ? constant(static::class . '::SERVICE') : ($this->serviceName ?? null);
        if ($serviceName) {
            try {
                $this->service = $this->service($serviceName);
                $this->activeService = $this->service;
            } catch (\Throwable $e) {
                // B-37: sessizce yutma. Davranis korunur (null birakilir), hat kayda gecer.
                $this->reportComponentResolutionFailure('SERVICE', (string) $serviceName, $e);
            }
        }
        $modelName = defined(static::class . '::MODEL') ? constant(static::class . '::MODEL') : ($this->modelName ?? null);
        if ($modelName) {
            try {
                $this->model = $this->model($modelName);
            } catch (\Throwable $e) {
                // B-37: sessizce yutma. Davranis korunur (null birakilir), hat kayda gecer.
                $this->reportComponentResolutionFailure('MODEL', (string) $modelName, $e);
            }
        }

        // 2. Identity Discovery is now autonomously handled by BaseContextTrait::bootBaseContext()
        // which triggers extractIdentity() Hierarchically! 🧬🏙️⚓靶
    }

    /**
     * B-37: Cozumleme hatasini gorunur kil (eski sessiz davranis DEGISTIRILMEZ).
     *
     * Servis/model cozumlenemezse onceki kod null birakip sessizce devam ediyordu;
     * bu metot o davranisi korur, yalnizca hat kaydini birakir. Log yazimi da
     * patlarsa (disk yok vb.) tekrar sessizce gecilmemesi icin ic try/catch vardir.
     *
     * @param string $tur SERVICE | MODEL
     */
    protected function reportComponentResolutionFailure(string $tur, string $ad, \Throwable $e): void
    {
        try {
            $this->logWorkspace('system')?->error(
                $tur . ' cozumlemesi basarisiz: ' . $ad,
                [
                    'controller' => static::class,
                    'exception'  => get_class($e),
                    'reason'     => $e->getMessage(),
                ]
            );
        } catch (\Throwable $logHatasi) {
            // Log kanali da kullanilamiyorsa akis bozulmaz (eski davranis) ama
            // hat yine de gorunur: PHP error_log en son cayak.
            error_log('[RBN] cozumleme hatasi loglanamadi: ' . $logHatasi->getMessage());
        }
    }

    /**
     * Prevent robots from indexing/following the current response (Full Shield Default). 🛡️⚓
     */
    public function noIndex(string $directives = 'noindex, nofollow, noarchive, nosnippet, noimageindex'): self
    {
        $this->service('seo')->noIndex($directives);
        return $this;
    }
}
