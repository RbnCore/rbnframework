<?php

namespace Rbn\Framework\Core\Support\Bridges\Helpers\Library;

/**
 * TextHelper - Metin işleme ve SEO araçları
 */
class TextHelper
{
    /** Türkçe karakterler listesi 🇹🇷 */
    public const TURKISH_CHARS = ['ç', 'ğ', 'ı', 'ö', 'ş', 'ü', 'Ç', 'Ğ', 'I', 'İ', 'Ö', 'Ş', 'Ü'];

    /** İngilizce karşılıkları 🇬🇧 */
    public const ENGLISH_CHARS = ['c', 'g', 'i', 'o', 's', 'u', 'C', 'G', 'I', 'I', 'O', 'S', 'U'];

    /**
     * Metin kısaltma (Türkçe uyumlu)
     */
    public function truncate($text, $length = 100, $suffix = '...'): string
    {
        if (mb_strlen($text, 'UTF-8') <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length, 'UTF-8') . $suffix;
    }

    /**
     * Metni hem kısaltır hem de escape eder (Güvenli kısaltma)
     */
    public function cutText($text, $length = 100, $suffix = '...'): string
    {
        return htmlspecialchars($this->truncate($text, $length, $suffix));
    }

    /**
     * Türkçe slug oluştur
     */
    public function turkishSlug($text): string
    {
        $text = str_replace(self::TURKISH_CHARS, self::ENGLISH_CHARS, (string) $text);
        $text = preg_replace('/[^a-zA-Z0-9\-_]+/', '-', $text);
        $text = preg_replace('/[\-_]+/', '-', $text);

        return strtolower(trim($text, '-'));
    }

    /**
     * Türkçe karakterleri İngilizce karşılıklarıyla değiştirir.
     */
    public function toEnglishAlphabet(string $text): string
    {
        return str_replace(self::TURKISH_CHARS, self::ENGLISH_CHARS, $text);
    }

    /**
     * İsim baş harfleri
     */
    public function getInitials($name, int $maxChars = 2): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }

        $parts = preg_split('/\s+/', $name);
        $initials = '';

        for ($i = 0; $i < min(count($parts), $maxChars); $i++) {
            if (!empty($parts[$i])) {
                $initials .= mb_substr($parts[$i], 0, 1);
            }
        }

        if (count($parts) === 1 && $maxChars === 2 && mb_strlen($parts[0]) > 1) {
            $initials = mb_substr($parts[0], 0, 1) . mb_substr($parts[0], 1, 1);
        }

        return mb_strtoupper($initials);
    }

    /**
     * Türkçe Uyumlu Title Case (İsim & Soyisim Baş Harfleri Büyük) 🇹🇷✨
     */
    public function nameTitleCase($name): string
    {
        if (empty($name)) {
            return '';
        }

        $words = preg_split('/\s+/u', trim((string)$name));
        $result = [];

        foreach ($words as $word) {
            if ($word === '') continue;

            // Türkçe küçük harfe çevir
            $lower = str_replace(['I', 'İ'], ['ı', 'i'], $word);
            $lower = mb_strtolower($lower, 'UTF-8');

            // İlk harfi Türkçe büyük yap
            $firstChar = mb_substr($lower, 0, 1, 'UTF-8');
            $rest = mb_substr($lower, 1, null, 'UTF-8');

            if ($firstChar === 'i') {
                $upperFirst = 'İ';
            } elseif ($firstChar === 'ı') {
                $upperFirst = 'I';
            } else {
                $upperFirst = mb_strtoupper($firstChar, 'UTF-8');
            }

            $result[] = $upperFirst . $rest;
        }

        return implode(' ', $result);
    }
}


