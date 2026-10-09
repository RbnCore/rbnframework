<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Bridges\Helpers\Library;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * MetaSeoHelper - Centralized SEO Metadata Architect 🧠🛰️⚓
 * RBN Framework Standard.
 */
class MetaSeoHelper extends BaseComponent
{
    /**
     * Stop words'lerden arındırılmış, cümle/bölüm ayırt edebilen ve askıda kelime bırakmayan ideal SEO slugı üretir 🌐
     */
    public function seoSlug(string $title, int $maxWords = 6): string
    {
        $cleanTitle = trim($title);
        if ($cleanTitle === '') {
            return '';
        }

        // 1. Çok satırlı veya çift cümleli başlıkları tespit et (\r, \n, ?, !, :, -)
        $lines = preg_split('/[\r\n]+/', $cleanTitle);
        if (!empty($lines)) {
            $firstLine = trim($lines[0]);
            $wordsInFirstLine = preg_split('/\s+/', $firstLine);
            if (count($wordsInFirstLine) >= 3) {
                $cleanTitle = $firstLine;
            }
        }

        // Cümle ve alt başlık ayraçları (: , - , – , — , | , // , ? , ! , . )
        $delimiters = [' : ', ': ', ' - ', ' – ', ' — ', ' | ', ' // ', '? ', '! ', '. '];
        foreach ($delimiters as $delim) {
            if (str_contains($cleanTitle, $delim)) {
                $parts = explode($delim, $cleanTitle);
                $firstPart = trim($parts[0]);
                $wordsInFirstPart = preg_split('/\s+/', $firstPart);
                if (count($wordsInFirstPart) >= 3) {
                    $cleanTitle = $firstPart;
                    break;
                }
            }
        }

        // 2. Türkçe Karakterleri Dönüştür ve Slug Temelini Hazırla
        $slug = $this->helper('text')->turkishSlug($cleanTitle);
        $parts = explode('-', $slug);

        // 3. Stop Words (Gereksiz bağlaçlar - 'su' gibi hayati isimler hariç tutulur)
        $stopWords = [
            've', 'ile', 'veya', 'ya', 'yada', 'icin', 'mi', 'mu', 'de', 'da', 'ki', 'ama',
            'fakat', 'ise', 'en', 'bir', 'cok', 'gibi', 'bu', 'o', 'daha', 'her',
            'tum', 'butun', 'bazi', 'olan', 'olarak', 'dolayi'
        ];

        $filtered = [];
        foreach ($parts as $word) {
            if ($word === '') {
                continue;
            }
            if (in_array($word, $stopWords, true)) {
                continue;
            }
            // Tek harfli harfleri eler, sayıları (5, 16 vb.) ve 2+ harfli kelimeleri tutar
            if (strlen($word) === 1 && !is_numeric($word)) {
                continue;
            }
            $filtered[] = $word;
        }

        if (empty($filtered)) {
            return $slug ?: 'icerik';
        }

        // 4. Kelime Limiti Uygula (İdeal SEO standardı: 5-6 kelime)
        if (count($filtered) > $maxWords) {
            $filtered = array_slice($filtered, 0, $maxWords);
        }

        // 5. Cümle sonunda tek başına anlam ifade etmeyen edatları temizle (grammatical postpositions)
        $danglingEnds = [
            'gore', 'kadar', 'uzere', 'uzerine', 'dogru', 'dolayi', 'ragmen', 'ait'
        ];

        while (count($filtered) > 2 && in_array(end($filtered), $danglingEnds, true)) {
            array_pop($filtered);
        }

        return implode('-', $filtered);
    }

    /**
     * Otonom SEO Paketi (Slug + Title + Description + Keywords) 🧬🛰️⚓
     * 
     * @param string $title Orijinal başlık
     * @param int $limit SEO başlığı karakter sınırı
     * @return array ['slug' => '...', 'seo_title' => '...', 'seo_description' => '...', 'seo_keywords' => '...']
     */
    public function autonomousSeo(string $title, int $limit = 70): array
    {
        // 1. Akıllı Slug (Stop words temizliği)
        $finalSlug = $this->seoSlug($title);
        $parts = explode('-', $finalSlug);

        // 2. Akıllı SEO Başlığı (Bağlaçlardan arındırılmış ve Title Case)
        $cleanWords = array_map(fn($w) => mb_convert_case($w, MB_CASE_TITLE, "UTF-8"), $parts);
        $seoTitle = implode(' ', $cleanWords);

        if (mb_strlen($seoTitle) > $limit) {
            $truncated = mb_substr($seoTitle, 0, $limit);
            $lastSpace = mb_strrpos($truncated, ' ');
            $seoTitle = ($lastSpace !== false) ? mb_substr($truncated, 0, $lastSpace) : $truncated;
        }

        // Meta açıklama oluştur (kısaltma)
        $seoDescription = $this->generateMetaDescription($title);

        // 3. Akıllı SEO Anahtar Kelimeleri (Tekil kelimeler yerine anlamlı öbekler)
        $titleParts = explode(':', $title, 2);
        $mainTitle = trim($titleParts[0]);
        $subTitle = isset($titleParts[1]) ? trim($titleParts[1]) : '';

        $keywords = [];
        $keywords[] = $mainTitle;
        if (!empty($subTitle)) {
            $keywords[] = $subTitle;
        }
        $keywords[] = $mainTitle . ' Rehberi';

        $seoKeywords = implode(', ', $keywords);

        $format = $this->helper('format');

        return [
            'slug' => $finalSlug,
            'seo_title' => $format->seoCleanText($seoTitle),
            'seo_description' => $format->seoCleanText($seoDescription),
            'seo_keywords' => $format->seoCleanText($seoKeywords)
        ];
    }

    /**
     * Meta açıklama oluştur (SEO için) 🧬
     * 
     * @param string $text Ham metin
     * @param int $length Karakter sınırı
     * @return string Temizlenmiş ve kısaltılmış meta açıklama
     */
    public function generateMetaDescription(string $text, int $length = 160): string
    {
        $text = $this->helper('datasweep')->sweepText($text);

        $text = $this->helper('text')->truncate($text, $length);

        $format = $this->helper('format');
        return $format->seoCleanText($text);
    }
}
