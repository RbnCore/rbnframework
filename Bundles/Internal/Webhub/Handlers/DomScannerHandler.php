<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Webhub\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * DomScannerHandler - Live DOM & Performance Analysis (Max 45 Points)
 * RBN 3.5 Handler Pipeline
 */
class DomScannerHandler extends BaseSeoHandler
{
    public function execute(array $params = []): array
    {
        $this->totalScore = 0;
        $this->report = [];

        $baseUrl = $params['base_url'] ?? '';
        
        $startTime = microtime(true);
        $response = $this->remote->get($baseUrl, [], [], ['timeout' => 5, 'curl' => [CURLOPT_SSL_VERIFYPEER => false]]);
        $ttfb = microtime(true) - $startTime;

        // 1. Performans Analizi: Sunucu Yanıt Süresi (Max 5 Puan)
        $this->award('Sunucu Yanıt Hızı (TTFB < 1sn)', $ttfb < 1.0, 5);

        if (!isset($response['raw']) || empty($response['raw'])) {
            $this->award('Canlı HTML DOM Taraması Yapılabildi mi?', false, 40); // Geri kalan 40 puan HTML olmadığı için iptal
            return [
                'score' => $this->totalScore,
                'report' => $this->report
            ];
        }

        $html = $response['raw'];

        // 2. Favicon HTML Kontrolü (Max 5 Puan)
        $hasFavicon = preg_match('/<link[^>]+rel=["\'](shortcut icon|icon)["\']/i', $html) === 1;
        $this->award('Favicon Simgesi (Canlı HTML Üzerinde Kurulum)', $hasFavicon, 5);

        // 3. Temel HTML Etiketleri (Max Toplam 7 Puan)
        $this->award('HTML Dil Tanımlaması (lang="tr" vb.)', preg_match('/<html[^>]+lang=["\']([a-zA-Z\-]+)["\']/i', $html) === 1, 2);
        $this->award('Mobil Uyumluluk Etiketi (Viewport)', preg_match('/<meta[^>]+name=["\']viewport["\']/i', $html) === 1, 2);
        $this->award('Kopya İçerik Önlemi (Canonical URL)', preg_match('/<link[^>]+rel=["\']canonical["\']/i', $html) === 1, 3);

        // 4. H1 Etiketi Kontrolü (Max 10 Puan)
        $h1Count = preg_match_all('/<h1[^>]*>/i', $html);
        $this->award('Ana Başlık (H1) Etiketi ve Tekil Kullanım', $h1Count === 1, 10, ($h1Count > 1 ? 5 : 0));

        // 5. İmajların Alt Etiketleri (Max 5 Puan)
        $imgCount = preg_match_all('/<img[^>]+>/i', $html, $images);
        if ($imgCount > 0) {
            $altCount = 0;
            foreach ($images[0] as $img) {
                if (preg_match('/alt=["\']([^"\']*)["\']/', $img, $matches) && trim($matches[1]) !== '') $altCount++;
            }
            $ratio = $altCount / $imgCount;
            $this->award('Görsel Açıklamaları (Alt Etiketleri > %80 Dolu)', $ratio >= 0.8, 5, ($ratio >= 0.5 ? 2 : 0));
        } else {
            $this->award('Görsel Açıklamaları (İmaj Bulunamadı, Puan Korundu)', true, 5); 
        }

        // 6. Open Graph (OG) Analizi (Max 8 Puan)
        $ogCount = 0;
        if (str_contains($html, 'og:title')) $ogCount++;
        if (str_contains($html, 'og:description')) $ogCount++;
        if (str_contains($html, 'og:image')) $ogCount++;
        
        $this->award('Open Graph (Facebook/LinkedIn) Paylaşım Kartları', $ogCount === 3, 8, ($ogCount > 0 ? 4 : 0));

        // 7. Twitter Cards Analizi (Max 5 Puan)
        $twCount = 0;
        if (str_contains($html, 'twitter:card')) $twCount++;
        if (str_contains($html, 'twitter:title')) $twCount++;
        
        $this->award('Twitter (X) Paylaşım Kartları', $twCount >= 2, 5, ($twCount === 1 ? 2 : 0));

        // 8. Yapılandırılmış Veri (Schema.org / JSON-LD / Microdata) Analizi (Max 5 Puan)
        $hasSchema = str_contains($html, 'application/ld+json') || 
                     str_contains($html, 'schema.org') || 
                     str_contains($html, 'itemscope') || 
                     str_contains($html, '@schema');
        $this->award('Arama Motoru Şema İşaretlemesi (Schema / JSON-LD)', $hasSchema, 5);

        return [
            'score' => $this->totalScore,
            'report' => $this->report
        ];
    }
}
