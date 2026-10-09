<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Http\Security\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * OriginHandler - The Request Source Actor 🛡️⛵
 * 
 * RBN Framework: Atomic actor for verifying HTTP Origin and Referer headers.
 * Protects against CSRF and Unauthorized Cross-Site Form submissions.
 */
class OriginHandler extends BaseComponent
{
    /**
     * Validate that the request originates from an allowed domain.
     * 
     * @param array $allowedOrigins Custom whitelist for the current request.
     * @param bool $strict If true, missing headers will cause rejection.
     */
    public function validate(array $allowedOrigins = [], bool $strict = false): bool
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
        $referer = $_SERVER['HTTP_REFERER'] ?? null;

        // 0. If both are missing, it might be a direct API/Curl/Postman request.
        if (is_null($origin) && is_null($referer)) {
            // [F-14 · 2026-10-04 · team member] LOG-ONLY GÖZLEM: Origin ve
            // Referer yok. KARAR DEĞİŞMİYOR (aşağıdaki `!$strict` aynen korunur);
            // yalnız bu isteklerin sayısı `security` kanalında özetlenir, çünkü
            // `strict`'i açmak kararı **ölçüm olmadan** verilemez.
            $this->artirLogOnlySayac();

            return !$strict; // Pass if not strict (API friendly).
        }

        // 1. Build the list of trusted domains
        $trustedDomains = $this->getTrustedDomains($allowedOrigins);

        // 2. Validate Origin (Header exists in AJAX/Modern browsers)
        if (!is_null($origin)) {
            return $this->isDomainTrusted($origin, $trustedDomains);
        }

        // 3. Fallback to Referer
        if (!is_null($referer)) {
            return $this->isDomainTrusted($referer, $trustedDomains);
        }

        return false;
    }

    /**
     * Compiles a list of trusted domains from config and custom list.
     */
    private function getTrustedDomains(array $customList = []): array
    {
        $projectUrl = \Rbn\Framework\Core\System\Paths\Paths::project()->baseUrl();
        $baseDomain = parse_url($projectUrl, PHP_URL_HOST);

        $rawHost = $_SERVER['HTTP_HOST'] ?? '';
        $requestHost = str_contains($rawHost, ':') ? explode(':', $rawHost)[0] : $rawHost;

        $trusted = [
            $baseDomain,
            $requestHost,
            'localhost',
            '127.0.0.1'
        ];

        // Merge with custom allowed origins
        foreach ($customList as $url) {
            if (!is_string($url)) {
                continue;
            }
            $host = parse_url($url, PHP_URL_HOST);
            // false = bozuk URL: listeye girmez; null = şemasız ("example.com"): ham değer.
            if ($host === false) {
                continue;
            }
            $trusted[] = $host ?? $url;
        }

        return array_unique(array_filter($trusted));
    }

    /**
     * [F-14 · LOG-ONLY] Ortak log-only sayacı artırır.
     *
     * Sayaç deposu TEK YERDE (`FormGuardHandler::$logOnlySayaclar`) tutulur:
     * iki ayrı depo = iki ayrı gerçek = "ölçtüm" dediğimiz sayı başkasınınki
     * olabilirdi. Buradaki metot yalnız o tek depoyu günceller; karar
     * (`!$strict`) bu çağrıdan ÖNCE/SONRA değişmez.
     */
    private function artirLogOnlySayac(): void
    {
        try {
            $this->handler('formGuard')
                ->artirLogOnlySayac(FormGuardHandler::SAYAC_ORIJIN_YOK);
        } catch (\Throwable $e) {
            // Gözlem katmanı asla kararı bozamaz (fail-open gözlem).
        }
    }

    /**
     * Checks if a specific source matches any trusted domain.
     */
    private function isDomainTrusted(string $source, array $trustedDomains): bool
    {
        $sourceHost = parse_url($source, PHP_URL_HOST);
        // false = ayrıştırılamayan kaynak (ör. "////evil.example/x"): güvenilmez; TypeError/500 yok.
        if ($sourceHost === false) {
            return false;
        }
        $sourceHost ??= $source;

        foreach ($trustedDomains as $trusted) {
            // Support simple wildcard (e.g. *.example.com)
            if (str_contains($trusted, '*')) {
                $pattern = '/^' . str_replace('\*', '.*', preg_quote($trusted, '/')) . '$/i';
                if (preg_match($pattern, $sourceHost)) {
                    return true;
                }
            }

            // Direct match (Case-insensitive)
            if (strcasecmp($sourceHost, $trusted) === 0) {
                return true;
            }
        }

        return false;
    }
}
