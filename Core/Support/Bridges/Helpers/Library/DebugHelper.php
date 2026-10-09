<?php

namespace Rbn\Framework\Core\Support\Bridges\Helpers\Library;

use Rbn\Framework\Core\System\Kernel\Base\PreBoot;


/**
 * DebugHelper - Global Debugging Araçları
 * Veri yazdırma, debug ve dump işlemleri için merkezi sınıf.
 * Production ortamında otomatik olarak sessiz kalır.
 */
class DebugHelper
{
    /**
     * Debugging için veri yazdırma - Güvenli ve esnek
     * 
     * @param mixed $data Yazdırılacak veri
     * @param bool $die İşlem sonrası kod dursun mu?
     * @param string $title Başlık (Opsiyonel)
     */
    public function prePrint($data, bool $die = false, string $title = ''): void
    {
        // Production'da çalışmasın
        // [FW-096-D8] TEK okuyucu: `PreBoot::isProductionDeclared()`
        // (`secrets.php` `app.environment`; yoksa production).
        if (PreBoot::isProductionDeclared()) {
            return;
        }

        echo '<div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; padding: 15px; margin: 10px 0; font-family: monospace; z-index: 99999; position: relative;">';

        if (!empty($title)) {
            echo '<h4 style="color: #495057; margin: 0 0 10px 0; border-bottom: 1px solid #dee2e6; padding-bottom: 5px;">' . htmlspecialchars($title) . '</h4>';
        }

        echo '<pre style="margin: 0; white-space: pre-wrap; word-wrap: break-word;">';
        print_r($data);
        echo '</pre>';
        echo '</div>';

        if ($die) {
            die();
        }
    }

    /**
     * Hızlı debug için kısayol (Dump & Die)
     */
    public function dd($data, string $title = 'DEBUG'): void
    {
        $this->prePrint($data, true, $title);
    }

    /**
     * Hızlı dump için kısayol (Dump & Continue)
     */
    public function dump($data, string $title = ''): void
    {
        $this->prePrint($data, false, $title);
    }
}
