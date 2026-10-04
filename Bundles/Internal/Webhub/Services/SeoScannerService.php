<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Webhub\Services;

use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * SeoScannerService - The Real SEO Analyzer Pipeline Orchestrator 🤖🚀⚓
 * Standard: RBN 3.5 Masterpiece
 */
class SeoScannerService extends BaseService
{
    /**
     * Pipeline'ı (Tüm Handler'ları) çalıştırır ve tam kapsamlı Rapor + Tavsiye döner.
     */
    public function scan(): array
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = rtrim($protocol . $host, '/');

        $params = [
            'base_url' => $baseUrl,
            'protocol' => $protocol
        ];

        // 1. İşçi Pipeline'ını Çalıştır ve Sonuçları (Skor + Rapor) Topla
        $pipelineResult = $this->executePipeline([
            'seo.settings',
            'seo.core',
            'seo.dom'
        ], $params);

        // 2. Yapay Zeka Tavsiyesini (seo.advice Handler'ından) Al
        $adviceHandler = $this->handler('seo.advice');
        $adviceResult = $adviceHandler->execute([
            'score'  => $pipelineResult['score'],
            'report' => $pipelineResult['report']
        ]);

        return [
            'score'  => $pipelineResult['score'],
            'report' => $pipelineResult['report'],
            'advice' => $adviceResult['advice'] ?? '',
            'gemini' => $adviceResult['gemini'] ?? ''
        ];
    }

    /**
     * Get Centralized SEO Report (View-Ready) 🏛️📊
     * Standard: RBN 3.5 Masterpiece
     */
    public function getReport(): array
    {
        $settings = $this->service('settings');
        if (!$settings) return [];

        $seoData = $settings->read('seo') ?? [];
        $systemData = $settings->read('system') ?? [];
        $raw = array_merge($seoData, $systemData);

        $reportItems = json_decode($raw['seo-report'] ?? '[]', true);
        if (!is_array($reportItems)) {
            $reportItems = [];
        }
        $score = (int) ($raw['seo-score'] ?? 0);

        $passed = 0;
        $failed = 0;
        foreach ($reportItems as $item) {
            if ($item['passed'] ?? false) {
                $passed++;
            } else {
                $failed++;
            }
        }

        return [
            'stats' => (object) [
                'total' => count($reportItems),
                'passed' => $passed,
                'failed' => $failed,
                'score' => $score,
                'advice' => $raw['seo-advice'] ?? 'Analiz henüz tamamlanmadı.',
                'gemini' => $raw['seo-gemini-advice'] ?? null,
                'domain' => site_domain(),
                'date' => now('d.m.Y H:i')
            ],
            'details' => $reportItems,
            'display' => \Rbn\Framework\Bundles\Internal\Webhub\Models\SeoConfig::getScoreDisplay($score),
            'raw' => $raw
        ];
    }

    /**
     * Verilen Handler listesini sırayla çalıştırır ve sonuçları birleştirir.
     */
    private function executePipeline(array $handlers, array $params): array
    {
        $totalScore = 0;
        $fullReport = [];

        foreach ($handlers as $alias) {
            $handler = $this->handler($alias);
            $result = $handler->execute($params);
            
            $totalScore += $result['score'] ?? 0;
            $fullReport = array_merge($fullReport, $result['report'] ?? []);
        }

        return [
            'score'  => (int) min(100, $totalScore),
            'report' => $fullReport
        ];
    }
}
