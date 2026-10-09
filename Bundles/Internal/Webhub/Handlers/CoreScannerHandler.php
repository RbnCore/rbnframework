<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Webhub\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * CoreScannerHandler - Network & Core Files Analysis (Max 30 Points)
 * RBN Framework Handler Pipeline
 */
class CoreScannerHandler extends BaseSeoHandler
{
    public function execute(array $params = []): array
    {
        $this->totalScore = 0;
        $this->report = [];

        $baseUrl = $params['base_url'] ?? '';
        $protocol = $params['protocol'] ?? '';

        // 1. SSL/HTTPS Kontrolü (Max 5 Puan)
        $this->award('SSL / HTTPS Güvenli Bağlantı (Ağ Protokolü)', $protocol === 'https://', 5);

        // 2. Robots.txt Kontrolü (Max 10 Puan)
        $this->award('Robots.txt Arama Motoru Yönerge Dosyası (Canlı HTTP Kontrolü)', $this->checkUrlExists($baseUrl . '/robots.txt'), 10);

        // 3. Sitemap Kontrolü (Max 15 Puan)
        // Favicon kontrolünü DOM'a aktardığımız için 5 puan buraya eklendi
        $this->award('Sitemap.xml Site Haritası Varlığı (Canlı HTTP Kontrolü)', $this->checkUrlExists($baseUrl . '/sitemap.xml'), 15);

        return [
            'score' => $this->totalScore,
            'report' => $this->report
        ];
    }

    /**
     * Verilen URL'e cURL GET isteği atarak ayakta olup olmadığını test eder.
     * RBN Framework dinamik dosyaları HEAD isteklerinde hata (HTTP 500) verebildiği için
     * GET isteği kullanıyoruz.
     */
    private function checkUrlExists(string $url): bool
    {
        $response = $this->remote->get($url, [], [], [
            'timeout' => 3,
            'connect_timeout' => 2,
            'curl' => [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]
        ]);

        return isset($response['http_code']) && $response['http_code'] >= 200 && $response['http_code'] < 400;
    }
}
