<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Http\Security\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\Support\Facades\Validation;

/**
 * UrlSafetyHandler - URL Güvenlik Analiz Motoru 🧬🛰️
 * 
 * RBN 3.5: Masterpiece Core Component for content filtration.
 * Analyzes URLs and titles for prohibited keywords, domains, and BTK blocks.
 */
class UrlSafetyHandler extends BaseComponent
{
    /**
     * Executes the raw threat analysis for a URL and optional title. 🧬🔍
     */
    public function analyze(string $url, ?string $title = ''): array
    {
        $url = strtolower($url);
        $title = strtolower($title ?? '');

        // 1. Keyword Analysis (Spam/Malicious Words) 🕵️‍♂️
        $spamWords = Validation::get('security.SPAM_WORDS') ?? [];
        
        foreach ($spamWords as $keyword) {
            if (str_contains($url, $keyword)) {
                return [
                    'is_safe' => false,
                    'reason'  => 'keyword_match',
                    'trigger' => $keyword
                ];
            }

            if (!empty($title) && str_contains($title, $keyword)) {
                return [
                    'is_safe' => false,
                    'reason'  => 'title_keyword_match',
                    'trigger' => $keyword
                ];
            }
        }

        // 2. Domain & TLD Analysis 🌐
        $prohibitedDomains = Validation::get('security.PROHIBITED_DOMAINS') ?? [];
        foreach ($prohibitedDomains as $domain) {
            if ($this->matchesProhibitedDomain($url, (string) $domain)) {
                return [
                    'is_safe' => false,
                    'reason'  => 'prohibited_domain',
                    'trigger' => $domain
                ];
            }
        }

        // 3. DNS/BTK Analysis (IP Based Blocking) 🚧
        if ($this->checkBtkBlock($url)) {
            return [
                'is_safe' => false,
                'reason'  => 'btk_block',
                'trigger' => 'BTK_IP_MATCH'
            ];
        }

        return ['is_safe' => true, 'reason' => null, 'trigger' => null];
    }

    /**
     * Yasakli alan adi eslesmesi yalniz HOST uzerinde yapilir (yol/sorgu degil):
     * ".tld" girdisi son-ek, noktali girdi tam/son-ek, noktasiz girdi tam etiket eslesir.
     */
    private function matchesProhibitedDomain(string $url, string $domain): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        $domain = strtolower(trim($domain));
        if (!is_string($host) || $host === '' || $domain === '') {
            return false;
        }
        $host = rtrim(strtolower($host), '.');

        if ($domain[0] === '.') {
            return str_ends_with($host, $domain);
        }
        if (str_contains($domain, '.')) {
            return $host === $domain || str_ends_with($host, '.' . $domain);
        }
        return in_array($domain, explode('.', $host), true);
    }

    /**
     * Host IP'lerini cozer: gecersiz ad sorgulanmaz, IP aynen doner, sonuc surec icinde
     * onbelleklenir ve surec basina sorgu sayisi sinirlidir (PHP'de DNS zaman asimi ayari yoktur).
     *
     * @return string[]
     */
    private function resolveHost(string $host): array
    {
        static $cache = [];
        static $lookups = 0;

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }
        if (!filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            return [];
        }
        if (isset($cache[$host])) {
            return $cache[$host];
        }
        if ($lookups >= 5) {
            return [];
        }
        $lookups++;
        $ips = gethostbynamel($host);
        return $cache[$host] = is_array($ips) ? $ips : [];
    }

    /**
     * Checks if the domain resolves to BTK-blocked IP ranges. 📡
     */
    private function checkBtkBlock(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) return false;

        $ips = $this->resolveHost($host);
        if (!$ips) return false;

        $btkIps = Validation::get('security.BTK_BLOCK_IPS') ?? [];
        foreach ($ips as $ip) {
            if (in_array($ip, $btkIps)) {
                return true;
            }
        }

        return false;
    }
}
