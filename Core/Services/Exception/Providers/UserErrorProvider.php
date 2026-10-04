<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Exception\Providers;

use Rbn\Framework\Core\Services\Exception\Providers\Base\BaseExceptionProvider;
/**
 * UserErrorProvider - Standard Error Renderer 🎭🏹
 * 
 * RBN 3.5: Responsible for rendering standardized user-facing error views.
 * MASTERPIECE [LAYER 4]: Completely autonomous and RenderService-independent.
 */
class UserErrorProvider extends BaseExceptionProvider
{
    /**
     * Render the given error code using the autonomous Shield Hub. 🏛️🏹⚖️
     */
    public function render(int $code, array $data = []): bool
    {
        // FW-KARAR-2 / Z-1: Uretimde (RBN_DEV !== true) sistemin kendi hatasindan
        // gelen ham mesaj/dosya/satir/trace/sinif adi gizlenir; genel metin +
        // hata kimligi gosterilir, ayrinti ayni kimlikle mevcut hata kanalina
        // yazilir. `level = user` olan mesajlar (404, form dogrulama) KULLANICIYA
        // AITTIR ve dokunulmaz — aksi halde 404/form ekranlari bosalirdi.
        $level = (string) ($data['level'] ?? '');
        if ($level !== 'user') {
            $data = self::redactForPublicOutput(
                $data,
                'Sunucumuzda beklenmedik bir sorun oluştu.',
                self::GENERIC_HINT
            );
        }

        // 🎯 RBN 3.5: Masterpiece AJAX Diagnostic Support 🏹🛰️
        if ($this->isAjax()) {
            $this->renderJson(['code' => $code, 'data' => $data], $code);
            return true;
        }

        // 🛡️ RBN 3.5: Masterpiece Dynamic Mapping (SSoT) 🏛️🚀
        $meta = $this->getErrorMapping($code);

        $payload = array_merge([
            'code' => $code,
            'icon' => $meta['icon'],
            'title' => $data['title'] ?? $meta['title'],
            'type' => "Hata {$code}",
            'message' => $data['message'] ?? $meta['message'],
            'desc' => $data['desc'] ?? ($meta['desc'] ?? ''),
            'hint' => $data['hint'] ?? $meta['hint'],
            'isPreFlight' => false
        ], $data);

        // 🎯 RBN 3.5: Fallback Logic - Use specific file if exists, otherwise use 'standard'
        $viewPath = \Rbn\Framework\Core\System\Paths\Paths::framework()->resources("Views/Errors/{$code}.php");
        $view = file_exists($viewPath) ? (string) $code : 'standard';

        self::renderAutonomous($view, $payload, $code);
        return true;
    }

    /**
     * Centralized Error Metadata Mapping (Single Source of Truth) 🏺⚓
     */
    private function getErrorMapping(int $code): array
    {
        $map = [
            400 => [
                'icon' => 'bi bi-exclamation-circle',
                'title' => 'Geçersiz İstek',
                'message' => 'Gönderilen istek sunucu tarafından anlaşılamadı.',
                'desc' => 'İsteğinizin parametreleri veya formatı sistem standartlarına uygun görünmüyor.',
                'hint' => 'Lütfen girdiğiniz verileri kontrol edip tekrar deneyin.'
            ],
            401 => [
                'icon' => 'bi bi-lock',
                'title' => 'Yetkisiz Giriş',
                'message' => 'Bu alanı görmek için kimlik doğrulaması yapmanız gerekiyor.',
                'desc' => 'Erişim sağlamak için geçerli bir kullanıcı oturumuna sahip olmalısınız.',
                'hint' => 'Lütfen giriş yapın veya sistem yöneticisiyle görüşün.'
            ],
            403 => [
                'icon' => 'bi bi-shield-lock',
                'title' => 'Erişim Engellendi',
                'message' => 'Bu bölgeye erişim yetkiniz kısıtlanmıştır.',
                'desc' => 'Bir hata olduğunu düşünüyorsanız yöneticiniz ile iletişime geçin.',
                'hint' => 'Gerekli izinlere sahip olduğunuzdan emin olun.'
            ],
            404 => [
                'icon' => 'bi bi-compass',
                'title' => 'Sayfa Bulunamadı',
                'message' => 'Aradığınız belge sistemde bulunamadı veya taşınmış olabilir.',
                'desc' => 'Ulaşmaya çalıştığınız adres artık aktif değil veya hiç var olmadı.',
                'hint' => 'URL adresini kontrol edin veya ana sayfaya dönün.'
            ],
            405 => [
                'icon' => 'bi bi-slash-circle',
                'title' => 'Geçersiz Metot',
                'message' => 'İstek gönderilen HTTP metodu bu sayfa için desteklenmiyor.',
                'desc' => 'Bu kaynak üzerinden yalnızca izin verilen metotlar ile işlem yapılabilir.',
                'hint' => 'Lütfen istek tipini (GET/POST) kontrol edin.'
            ],
            429 => [
                'icon' => 'bi bi-hourglass-split',
                'title' => 'Çok Fazla İstek',
                'message' => 'Kısa süre içinde çok fazla istek gönderdiniz.',
                'desc' => 'Güvenlik protokollerimiz nedeniyle geçici olarak sınırlandırıldınız.',
                'hint' => 'Lütfen biraz bekleyip tekrar deneyin.'
            ],
            500 => [
                'icon' => 'bi bi-exclamation-octagon',
                'title' => 'Sistem Hatası',
                'message' => 'Sunucumuzda beklenmedik bir sorun oluştu.',
                'desc' => 'Sistem çekirdeğinde geçici bir teknik aksaklık meydana geldi.',
                'hint' => 'Teknik ekibimiz durumdan haberdar edildi.'
            ],
            503 => [
                'icon' => 'bi bi-tools',
                'title' => 'Bakım Modu',
                'message' => 'Sistem şu an iyileştirme çalışmaları nedeniyle kapalıdır.',
                'desc' => 'Daha güçlü bir altyapı için planlı bakım çalışması yürütülmektedir.',
                'hint' => 'Kısa süre sonra her şey daha güçlü şekilde dönecek.'
            ]
        ];

        return $map[$code] ?? [
            'icon' => 'bi bi-exclamation-triangle',
            'title' => 'Hata',
            'message' => 'Sistem beklenmedik bir durumla karşılaştı.',
            'desc' => 'Tanımlanamayan bir hata oluştu ve işlem durduruldu.',
            'hint' => 'Lütfen sistem yöneticisi ile iletişime geçin.'
        ];
    }
}
