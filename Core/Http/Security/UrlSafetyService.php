<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Http\Security;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\Support\Contracts\Base\BaseServiceInterface;

/**
 * UrlSafetyService - URL Güvenlik Sunum Katmanı 🛡️🎨
 * 
 * RBN Framework: Presentation layer for content filtration.
 * Decoupled and Core-integrated for system-wide security audits.
 */
class UrlSafetyService extends BaseService implements BaseServiceInterface
{
    /**
     * Executes a safety validation on a URL and returns UI-ready presentation data. 🧬🎻
     */
    public function validate(string $url, ?string $title = ''): array
    {
        // 1. Get RAW Report from Core Handler (The Brain) 🧬
        $report = $this->handler('urlSafety')->analyze($url, $title);

        if ($report['is_safe']) {
            return ['is_safe' => true, 'error' => null];
        }

        // 2. Presentation Logic: Translation & Messages 🎤🎨
        $error = match ($report['reason']) {
            'keyword_match'         => "URL yasaklı bir kelime içeriyor: '{$report['trigger']}'",
            'title_keyword_match'   => "Başlık yasaklı bir kelime içeriyor: '{$report['trigger']}'",
            'prohibited_domain'     => "Bu alan adı veya uzantı sistemimizde yasaklanmıştır.",
            'btk_block'             => "Bu web sitesine Türkiye'den erişim (BTK kararı ile) engellenmiş görünüyor.",
            default                 => "URL güvenlik kontrolünden geçemedi."
        };

        return [
            'is_safe' => false,
            'error'   => $error,
            'report'  => $report
        ];
    }
}
