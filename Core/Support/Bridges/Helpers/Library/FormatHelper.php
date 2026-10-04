<?php

namespace Rbn\Framework\Core\Support\Bridges\Helpers\Library;


/**
 * FormatHelper - Veri formatlama ve dönüşüm araçları
 */
class FormatHelper
{
    /**
     * Para formatı - Türk Lirası (RBN Bilişim özel)
     */


    public function currencyTL($amount, $showSymbol = true, $decimals = 2): string
    {
        if (!is_numeric($amount)) {
            return $showSymbol ? '0,00 ₺' : '0,00';
        }

        // Türkiye formatı: 1.234,56 TL
        $formatted = number_format($amount, $decimals, ',', '.');

        return $showSymbol ? $formatted . ' ₺' : $formatted;
    }

    /**
     * Çoklu para birimi formatı
     */
    public function formatCurrency($amount, $currency = 'TL', $locale = 'tr_TR'): string
    {
        $currencies = [
            'TL' => ['symbol' => '₺', 'code' => 'TRY'],
            'USD' => ['symbol' => '$', 'code' => 'USD'],
            'EUR' => ['symbol' => '€', 'code' => 'EUR'],
            'GBP' => ['symbol' => '£', 'code' => 'GBP']
        ];

        if (!isset($currencies[$currency])) {
            $currency = 'TL';
        }

        $symbol = $currencies[$currency]['symbol'];

        if ($locale === 'tr_TR') {
            // Türkiye formatı: 1.234,56 ₺
            return number_format($amount, 2, ',', '.') . ' ' . $symbol;
        } else {
            // US formatı: $1,234.56
            return $symbol . number_format($amount, 2, '.', ',');
        }
    }

    /**
     * Türkçe tarih formatı (RBN Bilişim özel)
     */
    public function formatDateTurkish($date, $format = 'full'): string
    {
        if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
            return '-';
        }

        $months = [
            'January' => 'Ocak',
            'February' => 'Şubat',
            'March' => 'Mart',
            'April' => 'Nisan',
            'May' => 'Mayıs',
            'June' => 'Haziran',
            'July' => 'Temmuz',
            'August' => 'Ağustos',
            'September' => 'Eylül',
            'October' => 'Ekim',
            'November' => 'Kasım',
            'December' => 'Aralık'
        ];

        $days = [
            'Monday' => 'Pazartesi',
            'Tuesday' => 'Salı',
            'Wednesday' => 'Çarşamba',
            'Thursday' => 'Perşembe',
            'Friday' => 'Cuma',
            'Saturday' => 'Cumartesi',
            'Sunday' => 'Pazar'
        ];

        try {
            $timestamp = is_numeric($date) ? $date : strtotime($date);

            if ($timestamp === false) {
                return '-';
            }

            switch ($format) {
                case 'short':
                    // 15 Ara 2024
                    $formatted = date('d M Y', $timestamp);
                    $shortMonths = [
                        'Jan' => 'Oca',
                        'Feb' => 'Şub',
                        'Mar' => 'Mar',
                        'Apr' => 'Nis',
                        'May' => 'May',
                        'Jun' => 'Haz',
                        'Jul' => 'Tem',
                        'Aug' => 'Ağu',
                        'Sep' => 'Eyl',
                        'Oct' => 'Eki',
                        'Nov' => 'Kas',
                        'Dec' => 'Ara'
                    ];
                    return str_replace(array_keys($shortMonths), array_values($shortMonths), $formatted);

                case 'medium':
                    // 15 Aralık 2024
                    $formatted = date('d F Y', $timestamp);
                    return str_replace(array_keys($months), array_values($months), $formatted);

                case 'full':
                    // 15 Aralık 2024 Pazar
                    $formatted = date('d F Y l', $timestamp);
                    $formatted = str_replace(array_keys($months), array_values($months), $formatted);
                    return str_replace(array_keys($days), array_values($days), $formatted);

                case 'datetime':
                    // 15 Aralık 2024 14:30
                    $formatted = date('d F Y H:i', $timestamp);
                    return str_replace(array_keys($months), array_values($months), $formatted);

                case 'time':
                    // 14:30
                    return date('H:i', $timestamp);

                case 'relative':
                    return $this->getRelativeTime($timestamp);

                case 'month':
                    // Aralık
                    $formatted = date('F', $timestamp);
                    return str_replace(array_keys($months), array_values($months), $formatted);

                default:
                    $formatted = date('d F Y', $timestamp);
                    return str_replace(array_keys($months), array_values($months), $formatted);
            }
        } catch (\Exception $e) {
            return '-';
        }
    }

    /**
     * Göreceli zaman (2 saat önce, 3 gün önce)
     */

    public function getRelativeTime($timestamp): string
    {
        // Eğer string ise (datetime format), timestamp'e çevir
        if (is_string($timestamp)) {
            $timestamp = strtotime($timestamp);
        }

        // Geçersiz timestamp kontrolü
        if (!$timestamp || $timestamp <= 0) {
            return '-';
        }

        $now = time();
        $diff = $now - $timestamp;

        if ($diff < 0) {
            return 'Az önce'; // Gelecek tarih gelirse
        } elseif ($diff < 60) {
            return 'Az önce';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' dakika önce';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' saat önce';
        } elseif ($diff < 2592000) {
            $days = floor($diff / 86400);
            return $days . ' gün önce';
        } elseif ($diff < 31536000) {
            $months = floor($diff / 2592000);
            return $months . ' ay önce';
        } else {
            $years = floor($diff / 31536000);
            return $years . ' yıl önce';
        }
    }

    /**
     * Telefon numarası formatı (Türkiye)
     */
    public function formatPhone($phone): string
    {
        // Null veya boş kontrolü
        if (empty($phone)) {
            return '-';
        }

        // Rakamlar dışındaki her şeyi temizle
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // 90 ile başlıyorsa (12 haneli) -> 0 ile başlat (11 haneli)
        if (strlen($phone) === 12 && str_starts_with($phone, '90')) {
            $phone = '0' . substr($phone, 2);
        }

        // Standart TR formatı (05xx...)
        if (strlen($phone) === 11 && str_starts_with($phone, '0')) {
            return substr($phone, 0, 1) . ' (' . substr($phone, 1, 3) . ') ' .
                substr($phone, 4, 3) . ' ' .
                substr($phone, 7, 2) . ' ' .
                substr($phone, 9, 2);
        }

        return $phone;
    }

    /**
     * WhatsApp linkini sadece numara olarak formatla (Görünüm için)
     */
    public function formatWhatsapp($waLink): string
    {
        if (empty($waLink))
            return '-';

        $prefix = 'https://wa.me/';
        $phone = str_replace($prefix, '', $waLink);

        return $this->formatPhone($phone);
    }

    /**
     * Dosya boyutu formatı
     */
    public function formatFileSize($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }


    /**
     * Yüzde formatı
     */
    public function formatPercentage($value, $decimals = 1): string
    {
        return number_format($value, $decimals, ',', '.') . '%';
    }

    /**
     * Metnin tahmini okuma süresini dakika cinsinden hesaplar.
     */
    public function estimatedReadTime(string $content, int $wpm = 200): int
    {
        $cleanContent = strip_tags($content);
        $wordCount = count(preg_split('/\s+/u', $cleanContent));
        return max(1, (int) ceil($wordCount / $wpm));
    }

    /**
     * SEO uyumlu temiz metin formatlar (HTML etiketlerini siler, tırnakları ve ampersand'ları temizler)
     */
    public function seoCleanText(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = str_replace([' & ', '&'], ' ve ', $text);
        $text = str_replace(['<', '>'], '', $text);
        $text = str_replace('"', "'", $text);
        return trim($text);
    }

    /**
     * Haftanın günleri dizisini döner 🕒
     */
    public function weekdaysMap(): array
    {
        return [
            1 => 'Pazartesi',
            2 => 'Salı',
            3 => 'Çarşamba',
            4 => 'Perşembe',
            5 => 'Cuma',
            6 => 'Cumartesi',
            7 => 'Pazar'
        ];
    }

    /**
     * Yıl ve Ay parametrelerine göre başlangıç ve bitiş tarih aralığını döner 📅
     *
     * @param int|null $year  Örn: 2026
     * @param int|null $month Örn: 8 (1-12)
     * @return array [?string $startDate, ?string $endDate]
     */
    public function dateRange(?int $year = null, ?int $month = null): array
    {
        if ($year !== null && $year > 0 && $month !== null && $month > 0) {
            $startDate = sprintf('%04d-%02d-01', $year, $month);
            $endDate   = date('Y-m-t', strtotime($startDate));
            return [$startDate, $endDate];
        } elseif ($year !== null && $year > 0) {
            return [sprintf('%04d-01-01', $year), sprintf('%04d-12-31', $year)];
        }

        return [null, null];
    }

    /**
     * Yıl ve Ay parametrelerine göre insan tarafından okunabilir dönem etiketi döner 🏷️

     *
     * Örnekler:
     * - periodLabel(2026, 8) ➡️ "Ağustos 2026"
     * - periodLabel(2026, null) ➡️ "2026 Yılı"
     * - periodLabel(null, 8) ➡️ "Tüm Yıllar (Ağustos Ayları)"
     * - periodLabel(null, null) ➡️ "Tüm Zamanlar"
     *
     * @param int|null $year  Örn: 2026
     * @param int|null $month Örn: 8 (1-12)
     * @return string
     */
    public function periodLabel(?int $year = null, ?int $month = null): string
    {
        if ($year && $month) {
            $monthName = $this->formatDateTurkish(sprintf('%04d-%02d-01', $year, $month), 'month');
            return "{$monthName} {$year}";
        } elseif ($year) {
            return "{$year} Yılı";
        } elseif ($month) {
            $monthName = $this->formatDateTurkish(sprintf('2026-%02d-01', $month), 'month');
            return "Tüm Yıllar ({$monthName} Ayları)";
        }

        return 'Tüm Zamanlar';
    }

    /**
     * User-Agent dizesini ayrıştırarak Tarayıcı, İşletim Sistemi ve İkon bilgilerini döner 🌐💻
     * 
     * @param string|null $ua
     * @return array{browser: string, icon: string, os: string, raw: string}
     */
    public function parseUserAgent(?string $ua = ''): array
    {
        $ua = (string) ($ua ?? '');
        $browser = 'Tarayıcı';
        $icon = 'ri-global-line';

        if (stripos($ua, 'Edg') !== false) {
            $browser = 'MS Edge';
            $icon = 'ri-edge-line';
        } elseif (stripos($ua, 'Chrome') !== false) {
            $browser = 'Chrome';
            $icon = 'ri-chrome-line';
        } elseif (stripos($ua, 'Firefox') !== false) {
            $browser = 'Firefox';
            $icon = 'ri-firefox-line';
        } elseif (stripos($ua, 'Safari') !== false) {
            $browser = 'Safari';
            $icon = 'ri-safari-line';
        } elseif (stripos($ua, 'Opera') !== false || stripos($ua, 'OPR') !== false) {
            $browser = 'Opera';
            $icon = 'ri-opera-line';
        } elseif (stripos($ua, 'bot') !== false || stripos($ua, 'crawl') !== false || stripos($ua, 'spider') !== false) {
            $browser = 'Bot / Robot';
            $icon = 'ri-robot-2-line';
        }

        $os = 'PC';
        if (stripos($ua, 'Windows') !== false) {
            $os = 'Windows';
        } elseif (stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false || stripos($ua, 'iOS') !== false) {
            $os = 'iOS';
        } elseif (stripos($ua, 'Android') !== false) {
            $os = 'Android';
        } elseif (stripos($ua, 'Mac') !== false) {
            $os = 'macOS';
        } elseif (stripos($ua, 'Linux') !== false) {
            $os = 'Linux';
        }

        return [
            'browser' => $browser,
            'icon'    => $icon,
            'os'      => $os,
            'raw'     => $ua,
        ];
    }
}




