<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Webhub\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * AdviceHandler - Generates AI SEO advice based on the total score and report.
 * RBN Framework Handler Pipeline
 */
class AdviceHandler extends BaseComponent
{
    /**
     * Puan durumuna ve kaybedilen raporlara göre tavsiye metni üretir.
     */
    public function execute(array $params = []): array
    {
        $score = $params['score'] ?? 0;
        $report = $params['report'] ?? [];

        // 1. Sistem Tavsiyesi Üret (Kural Tabanlı) 🎯
        $failedItems = [];
        foreach ($report as $item) {
            if (!$item['passed']) {
                $failedItems[] = $item['criteria'];
            }
        }
        $failedText = !empty($failedItems) ? implode(', ', $failedItems) : 'Kritik bir eksik bulunamadı';

        if ($score >= 80) {
            $systemAdvice = "SEO sağlığınız genel olarak **mükemmel** durumda. Küçük eksikleri de giderirseniz tam puan alabilirsiniz.";
        } elseif ($score >= 50) {
            $systemAdvice = "SEO sağlığınız genel olarak **iyi** durumda ancak bazı iyileştirmeler yapmanız gerekiyor. Özellikle: **{$failedText}** vb. kritik alanları düzeltirseniz zirveye ulaşırsınız.";
        } else {
            $systemAdvice = "Web sitenizin SEO puanı şu an **kritik** seviyede. Arama motorlarında görünürlük kazanmak için: **{$failedText}** alanlarındaki hataları acilen gidermelisiniz.";
        }

        // 2. Gemini AI Analizi Üret 🤖🧠
        $geminiAdvice = "Analiz yapılıyor...";
        try {
            $reportSummary = "";
            foreach ($report as $r) {
                $status = $r['passed'] ? "[TAMAM]" : "[EKSİK]";
                $reportSummary .= "{$status} {$r['criteria']} ({$r['earned']}/{$r['max']} puan)\n";
            }

            $prompt = "Sen profesyonel bir SEO uzmanısın. Bir web sitesinin teknik SEO analiz sonuçları aşağıdadır:
            - Toplam SEO Skoru: {$score}/100
            - Teknik Detaylar:
            {$reportSummary}
            
            Lütfen bu verileri analiz et ve site sahibine kısa, öz, fütüristik ve etkileyici bir tavsiye raporu yaz. 
            Teknik terimleri kullan ama anlaşılır ol. 
            
            KRİTİK FORMAT KURALLARI:
            1. Yanıtını doğrudan HTML formatında ver.
            2. Başlıklar için <h4>, paragraflar için <p>, listeler için <ul> ve <li> etiketlerini kullan.
            3. Markdown karakterleri (#, *, - vb.) ve KOD BLOKLARI (```) KESİNLİKLE kullanma.
            4. Yanıtın direkt analiz olsun, 'Merhaba' gibi giriş cümleleri kullanma.
            5. SADECE HTML etiketlerini döndür, başka açıklama metni ekleme.";

            $geminiService = $this->service('gemini');
            $gemini = $geminiService->ask($prompt, $geminiService->textModel, true);
            if ($gemini['status'] === 'success') {
                $geminiAdvice = $gemini['data'];
            } else {
                $geminiAdvice = "Gemini AI şu an yanıt veremiyor: " . ($gemini['message'] ?? 'Bilinmeyen hata');
            }
        } catch (\Exception $e) {
            $geminiAdvice = "AI Analizi sırasında bir hata oluştu: " . $e->getMessage();
        }

        return [
            'advice' => $systemAdvice,
            'gemini' => $geminiAdvice
        ];
    }
}
