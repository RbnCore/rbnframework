<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Services;

use Rbn\Framework\Core\Support\Contracts\Base\BaseProviderInterface;
use Rbn\Framework\Core\Base\Services\Traits\Provider\ActionProviderTrait;
use Rbn\Framework\Core\Base\BaseComponent;

/**
 * BaseProvider - Abstract Foundation for all RBN Providers 🏛️🎻⚓
 * 
 * RBN 3.5: Powered by BaseComponent DNA.
 * Unified under Services hiyerarşisi.
 */
abstract class BaseProvider extends BaseComponent implements BaseProviderInterface
{
    use ActionProviderTrait;

    /**
     * [B-63] `register()` eksikliği logunun throttle anahtarı etiketi.
     *
     * Değer/PII YOK; yalnız sınıf adı eklenir (Anayasa §9).
     */
    public const MISSING_REGISTER_THROTTLE_TAG = 'b63-missing-register';

    /**
     * Provider Constructor: Sync with Hub and Initialize 🏛️⚓
     */
    public function __construct(?BaseService $rbn = null)
    {
        // 🎼 RBN 3.5: [DNA SYNC] - Root bileşeni ayağa kaldır
        parent::__construct($rbn);

        // 🎻 RBN 3.5: [REGISTRATION LIFECYCLE]
        // Provider'ın kendi modellerini ve ayarlarını kurması için register() tetiklenir.
        if (method_exists($this, 'register')) {
            $this->register();
        } else {
            // [B-63 / FW-ALTYAPI-1 A-11] Sessiz atlama KALDIRILDI — kayıt
            // eksikliği artık GÖRÜNÜR. Ölçüm: framework ağacındaki 28 sağlayıcı
            // (tüm projelerle 41) HİÇBİRİ `register()` tanımlamıyor, yani
            // `method_exists` dalı her zaman `false` idi ve hiçbir iz kalmıyordu.
            //
            // NEDEN `PreflightException` DEĞİL: bu metot 28/41 sağlayıcıyı aynı
            // anda kırar ve hepsi meşru (kayıt gerektirmeyen) sağlayıcıdır.
            // Karar tablosu A-11 de "yalnız error_log" diyor. Bu yüzden:
            //   - davranış BİREBİR AYNI (çökme yok, istek düşme yok),
            //   - yalnız saatlik throttle'lu görünürlük satırı.
            $this->logMissingRegister();
        }
    }

    /**
     * [B-63] `register()` tanımlamayan sağlayıcı için görünürlük satırı 📊
     *
     * `LogThrottle::once()` saatlik + süreçler arası kapi kullanır; 41 sağlayıcı
     * her istekte kurulsa bile günlüğe saatte en fazla 41 satır düşer.
     */
    private function logMissingRegister(): void
    {
        try {
            if (!\Rbn\Framework\Core\Support\Bridges\Helpers\Library\LogThrottle::once(
                self::MISSING_REGISTER_THROTTLE_TAG . ':' . static::class
            )) {
                return;
            }
        } catch (\Throwable $e) {
            // Olcum katmani karari bozamaz; asil satir yine de yazilir.
        }

        error_log('[RBN] ' . static::class . ' register() metodunu TANIMLAMIYOR; '
            . 'kayit adimi sessizce atlandi (B-63 gorunurluk kaydi).');
    }

    // RBN 3.5: Redundant boot() and setContext() removed. 🚿
    // DNA inherited autonomously from BaseComponent and BaseContextTrait. 🧬⚓🚀
}
