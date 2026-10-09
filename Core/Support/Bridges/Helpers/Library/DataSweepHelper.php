<?php

namespace Rbn\Framework\Core\Support\Bridges\Helpers\Library;

/**
 * DataSweepHelper - Otonom Veri Süpürme ve Ayıklama Araçları 🧹🛰️⚓
 * 
 * RBN Framework: AI yanıtları, Web Scraping ve karmaşık metinlerden veri çekmek için tasarlanmıştır.
 * Adı gibi veriyi süpürüp temizler ve özünü çıkarır.
 */
class DataSweepHelper
{
    /**
     * AI Yanıtı İçinden JSON Bloklarını Süpürür ve Ayıklar 🧹🧼🛰️⚓
     * RBN Framework: Extract clean JSON (Array or Object) from dirty AI strings.
     */
    public function cleanJson(string $text): string
    {
        // Önce JSON bloğunu bul (Dizi veya Nesne)
        if (preg_match('/\{(?:[^{}]|(?R))*\}|\[(?:[^[\]]|(?R))*\]/s', $text, $matches)) {
            $json = $matches[0];
            return trim($json);
        }
        return '';
    }

    /**
     * AI Yanıtından JSON Süpürüp Doğrudan Decode Eder 🧹🧼🛰️
     */
    public function decodeJson(string $text, bool $associative = true): mixed
    {
        $json = $this->cleanJson($text);
        return json_decode($json ?: $text, $associative);
    }

    /**
     * Kirli Metni Süpürüp Temizler (Scraping Hazırlığı) 🚿🧹
     */
    public function sweepText(string $text): string
    {
        $text = strip_tags($text);
        $text = html_entity_decode($text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    /**
     * URL'den Domain Süpürür/Ayıklar 🌍🧹
     */
    public function extractDomain($url): string
    {
        if (empty($url)) {
            return '';
        }

        if (!preg_match('/^https?:\/\//', $url)) {
            $url = 'http://' . $url;
        }

        $parsed = parse_url($url);
        return $parsed['host'] ?? '';
    }

    /**
     * Veriyi Standart SEO/AI Dostu JSON Formatında Kodlar 🧹🧼🛰️
     */
    public function encodeJson(mixed $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Regex yardımıyla href içindeki hatalı/statik url yapılarını yakalayıp {{ url(...) }} yapısına zorlar.
     */
    public function sweepTemplateUrls(string $text, string $prefix = 'blog'): string
    {
        $escapedPrefix = preg_quote($prefix, '/');

        // Robust parsing of a href elements referencing the prefix path
        $text = preg_replace_callback(
            '/<a\s+([^>]*)href=(["\'])(.*?)\2([^>]*)/i',
            function ($matches) use ($escapedPrefix, $prefix) {
                $attrsBefore = $matches[1];
                $quote = $matches[2];
                $url = $matches[3];
                $attrsAfter = $matches[4];

                // Decode HTML entities (e.g. &#039;) and strip leading/trailing quotes or slashes
                $cleanUrl = html_entity_decode($url, ENT_QUOTES, 'UTF-8');
                $cleanUrl = trim($cleanUrl, " '\"/");

                // If it contains the helper signature, extract the inner path
                if (preg_match('/url\((.*?)\)/', $cleanUrl, $urlMatch)) {
                    $cleanUrl = trim($urlMatch[1], " '\"/");
                }

                // If the URL starts with our prefix (e.g. blog/...)
                if (str_starts_with($cleanUrl, $prefix . '/')) {
                    return '<a ' . $attrsBefore . 'href="{{ url(\'' . $cleanUrl . '\') }}"' . $attrsAfter;
                }

                return $matches[0];
            },
            $text
        );

        return $text;
    }

    /**
     * Base64Url encoding helper (URL-safe string transformation) 🧼🛰️
     */
    public function base64UrlEncode(string $data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    /**
     * Base64Url decoding helper 🧼🛰️
     */
    public function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }
}
