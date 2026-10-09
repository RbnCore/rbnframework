<?php

namespace Rbn\Framework\Core\Http;

use Rbn\Framework\Core\Support\Contracts\Http\AlertServiceInterface;
use Rbn\Framework\Core\Http\Engine\Traits\Alert\NotificationTrait;
use Rbn\Framework\Core\Http\Engine\Traits\Alert\StorageTrait;

/**
 * AlertService - Standardized Notification Engine 🔔🛡️⚓
 * 
 * High-Performance, modular alerting with hybrid session/cookie storage.
 * Synchronized with the official HTTP Request & Response engines.
 */
class AlertService implements AlertServiceInterface
{
    use NotificationTrait, StorageTrait;

    /**
     * Singleton instance accessor (Unified via helper) 🛰️
     */
    private static ?self $instance = null;

    /** @var bool Kilit mekanizması: Bir istekte sadece bir mesaj işlenir 🛡️ */
    protected bool $alertSent = false;

    public static function get(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Merkezi mesaj gönderim mantığı 🛰️
     */
    public function send(string $type, string $message, ?string $redirectUrl = null, ?string $message2 = null, array $data = []): void
    {
        // 🛡️ RBN Framework: Lock Mechanism
        // Eğer bu işlem döngüsünde zaten bir mesaj gönderildiyse (örneğin manuel alert() kullanımı),
        // handleResult gibi ikincil tetikleyicilerin mesajlarını yoksay.
        if ($this->alertSent) {
            return;
        }
        $this->alertSent = true;

        $display = $data['display'] ?? 'toast';
        $isAjax = request()->isAjax();

        // [GUVENLIK YAMASI 2026-10-01 / gorev 94] ACIK YONLENDIRME - IKINCI YOL.
        // Onceki hali: `$redirectUrl` dogrudan JSON'a yaziliyordu. AJAX yanitinda
        // `redirect()` HIC CAGRILMADI (asagida sadece !$isAjax dalinda cagriliyor),
        // yani RedirectTrait'in beyaz liste korumasi bu yolda HIC GECMIYORDU.
        // Sonuc: rbnService.js / rbnAlert.js `window.location.href = redirect`
        // ile saldirganin girdisini oldugu gibi yaziyordu. `redirect` degeri
        // `$this->request->input('redirect')` gibi dogrudan kullanici girdilerinden
        // gelir (or. SroGroupController::assignGroup satir 112) ve 257 form'un 45'i
        // data-rbn-ajax -> bu yol POST/redirect yolundan daha BASKIN.
        // Cozum: JSON'a YAZMADAN once ayni tek choke noktadan gecir.
        //
        // [PHP 8.3] `RedirectTrait::guvenliHedef()` seklinde STATIK cagri
        // DEYILDIR: trait statik metot cagrisi PHP 8.3'te "Calling static trait
        // method ... is deprecated" uyarisi basar ve o uyari JSON'dan ONCE
        // yazildigi icin JS `JSON.parse` cagrisini kirdi. `response()` Response
        // nesnesidir ve RedirectTrait'i kullanir, bu yuzdan bu instance
        // cagrisi hem temizdir hem de ayni mantigi tek kaynaktan ceker.
        //
        // NOT: `$redirectUrl` null ise dokunulmaz (mevru davranis korunur).
        if (is_string($redirectUrl) && $redirectUrl !== '') {
            $redirectUrl = response()->guvenliHedef($redirectUrl);
        }

        $alertPayload = [
            'type' => $type,
            'message' => $message,
            'message2' => $message2,
            'redirect' => $redirectUrl,
            'display' => $display,
            'timestamp' => now(),
            'data' => $data
        ];

        // 1. Hibrit Depolama (Session + Cookie if requested)
        if (!$isAjax || $redirectUrl) {
            $this->storeAlert($alertPayload);
        }

        // 2. AJAX Yanıtı (Müjdenin tarayıcıya ulaştırılması) 🛰️
        if ($isAjax) {
            response()->alertJson($alertPayload);
        }

        // 3. Normal Yönlendirme
        if ($redirectUrl && !$isAjax) {
            response()->redirect($redirectUrl);
        }
    }
}
