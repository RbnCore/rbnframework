<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Services;

use Rbn\Framework\Core\Support\Contracts\Base\BaseProviderInterface;
use Rbn\Framework\Core\Base\Services\Traits\Provider\ActionProviderTrait;
use Rbn\Framework\Core\Base\BaseComponent;

/**
 * BaseProvider - Abstract Foundation for all RBN Providers 🏛️🎻⚓
 * 
 * RBN Framework: Powered by BaseComponent DNA.
 * Unified under Services hiyerarşisi.
 */
abstract class BaseProvider extends BaseComponent implements BaseProviderInterface
{
    use ActionProviderTrait;

    /**
     * Provider Constructor: Sync with Hub and Initialize 🏛️⚓
     */
    public function __construct(?BaseService $rbn = null)
    {
        // 🎼 RBN Framework: [DNA SYNC] - Root bileşeni ayağa kaldır
        parent::__construct($rbn);

        // 🎻 RBN Framework: [REGISTRATION LIFECYCLE]
        // Sağlayıcı `register()` tanımlıyorsa tetiklenir (kendi model/ayar
        // kaydı için). [FW-095] Ölçüm: framework ağacındaki HİÇBİR sağlayıcı
        // `register()` tanımlamıyor; kayıt işi constructor/DNA ile yapılıyor.
        // Bu yüzden "register() yok" dalını her kurulumda loga yazmak saf
        // yanlış-pozitifti (canlı `error_log`'a saatte onlarca satır). Gürültü
        // KALDIRILDI; gerçek bir `register()` varsa cagri AYNEN sürer.
        if (method_exists($this, 'register')) {
            $this->register();
        }
    }

    // RBN Framework: Redundant boot() and setContext() removed. 🚿
    // DNA inherited autonomously from BaseComponent and BaseContextTrait. 🧬⚓🚀
}
